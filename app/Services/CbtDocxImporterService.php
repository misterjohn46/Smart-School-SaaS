<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CbtBank;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class CbtDocxImporterService
{
    /**
     * Parse file Word (.docx) dan masukkan butir soal beserta gambarnya ke CbtBank.
     *
     * @return array{total_imported: int, questions: array<int, array<string, mixed>>, errors: array<int, string>}
     */
    public function import(UploadedFile|string $file, CbtBank $bank): array
    {
        $filePath = is_string($file) ? $file : $file->getRealPath();

        $zip = new ZipArchive;
        if ($zip->open($filePath) !== true) {
            throw new \RuntimeException('Berkas Word tidak dapat dibuka atau korup.');
        }

        // 1. Ekstrak peta relasi (Relationships) untuk mendeteksi gambar di word/_rels/document.xml.rels
        $rels = $this->extractRelationships($zip);

        // 2. Baca dokumen utama (word/document.xml)
        $docXml = $zip->getFromName('word/document.xml');
        if (! $docXml) {
            $zip->close();
            throw new \RuntimeException('Berkas Word tidak memiliki struktur word/document.xml yang valid.');
        }

        // 3. Parse XML dan ekstrak paragraf + gambar
        $paragraphs = $this->parseDocumentXml($docXml, $zip, $rels);
        $zip->close();

        // 4. Susun butir-butir soal dari deretan paragraf
        $parsedQuestions = $this->structureQuestions($paragraphs);

        // 5. Simpan ke database
        $importedCount = 0;
        $savedQuestions = [];
        $errors = [];

        foreach ($parsedQuestions as $qData) {
            try {
                $isComplex = str_contains((string) ($qData['correct_answer'] ?? ''), ',');
                $questionType = $qData['question_type'] ?? ($isComplex ? 'multiple_choice' : 'multiple_choice');

                $newQuestion = $bank->questions()->create([
                    'question_text' => $qData['question_text'],
                    'question_type' => $questionType,
                    'options' => $qData['options'],
                    'correct_answer' => $qData['correct_answer'],
                    'explanation' => $qData['explanation'] ?? null,
                    'score_weight' => $qData['score_weight'] ?? 10,
                ]);

                $importedCount++;
                $savedQuestions[] = $newQuestion->toArray();
            } catch (\Throwable $e) {
                $errors[] = "Gagal menyimpan soal no {$qData['number']}: {$e->getMessage()}";
            }
        }

        // Perbarui total soal pada Bank Soal
        $bank->update([
            'total_questions' => $bank->questions()->count(),
        ]);

        return [
            'total_imported' => $importedCount,
            'questions' => $savedQuestions,
            'errors' => $errors,
        ];
    }

    /**
     * Ekstrak relasi berkas gambar dari word/_rels/document.xml.rels
     *
     * @return array<string, string> Map of rId => Target (e.g. 'rId4' => 'media/image1.png')
     */
    protected function extractRelationships(ZipArchive $zip): array
    {
        $rels = [];
        $relsXml = $zip->getFromName('word/_rels/document.xml.rels');
        if (! $relsXml) {
            return $rels;
        }

        $dom = new DOMDocument;
        @$dom->loadXML($relsXml);
        $relationships = $dom->getElementsByTagName('Relationship');

        foreach ($relationships as $rel) {
            /** @var DOMElement $rel */
            $id = $rel->getAttribute('Id');
            $type = $rel->getAttribute('Type');
            $target = $rel->getAttribute('Target');

            if (str_contains($type, 'image')) {
                $rels[$id] = $target;
            }
        }

        return $rels;
    }

    /**
     * Parse word/document.xml dan ambil urutan teks, tabel, serta gambar
     *
     * @param  array<string, string>  $rels
     * @return array<int, array{text: string, plain_text: string, has_image: bool, is_table?: bool}>
     */
    protected function parseDocumentXml(string $docXml, ZipArchive $zip, array $rels): array
    {
        $dom = new DOMDocument;
        @$dom->loadXML($docXml);

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
        $xpath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $xpath->registerNamespace('v', 'urn:schemas-microsoft-com:vml');

        // Pastikan direktori public storage cbt_questions tersedia
        if (! Storage::disk('public')->exists('cbt_questions')) {
            Storage::disk('public')->makeDirectory('cbt_questions');
        }

        $paragraphs = [];
        $bodyChildren = $xpath->query('/w:document/w:body/*');

        if (! $bodyChildren) {
            return $paragraphs;
        }

        foreach ($bodyChildren as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            if ($node->nodeName === 'w:p') {
                $parsed = $this->parseParagraphNode($node, $xpath, $zip, $rels);
                if ($parsed !== null) {
                    $paragraphs[] = $parsed;
                }
            } elseif ($node->nodeName === 'w:tbl') {
                $parsedTbl = $this->parseTableNode($node, $xpath, $zip, $rels);
                if ($parsedTbl !== null) {
                    $paragraphs[] = $parsedTbl;
                }
            } elseif ($node->nodeName === 'w:sdt') {
                $sdtChildren = $xpath->query('.//w:p | .//w:tbl', $node);
                if ($sdtChildren) {
                    foreach ($sdtChildren as $subNode) {
                        if (! $subNode instanceof DOMElement) {
                            continue;
                        }
                        if ($subNode->nodeName === 'w:p') {
                            $parsed = $this->parseParagraphNode($subNode, $xpath, $zip, $rels);
                            if ($parsed !== null) {
                                $paragraphs[] = $parsed;
                            }
                        } elseif ($subNode->nodeName === 'w:tbl') {
                            $parsedTbl = $this->parseTableNode($subNode, $xpath, $zip, $rels);
                            if ($parsedTbl !== null) {
                                $paragraphs[] = $parsedTbl;
                            }
                        }
                    }
                }
            }
        }

        return $paragraphs;
    }

    /**
     * Parse elemen paragraf OpenXML (<w:p>) menjadi teks HTML dan plain text
     *
     * @param  array<string, string>  $rels
     * @return array{text: string, plain_text: string, has_image: bool, is_table: bool}|null
     */
    protected function parseParagraphNode(DOMElement $p, DOMXPath $xpath, ZipArchive $zip, array $rels): ?array
    {
        $pHtml = '';
        $pPlainText = '';
        $hasImage = false;

        $childElements = $xpath->query('.//w:t | .//a:blip | .//v:imagedata | .//w:br', $p);
        if (! $childElements) {
            return null;
        }

        foreach ($childElements as $el) {
            if ($el->nodeName === 'w:t') {
                $text = $el->textContent;
                $pPlainText .= $text;
                $pHtml .= htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            } elseif ($el->nodeName === 'w:br') {
                $pPlainText .= "\n";
                $pHtml .= '<br/>';
            } elseif ($el->nodeName === 'a:blip' || $el->nodeName === 'v:imagedata') {
                /** @var DOMElement $el */
                $rId = $el->getAttribute('r:embed') ?: $el->getAttribute('r:id');
                if ($rId && isset($rels[$rId])) {
                    $target = $rels[$rId];
                    $zipEntryName = str_starts_with($target, 'media/') ? 'word/'.$target : 'word/'.ltrim($target, '/');
                    $imageBinary = $zip->getFromName($zipEntryName);

                    if ($imageBinary) {
                        $ext = strtolower(pathinfo($target, PATHINFO_EXTENSION) ?: 'png');
                        $fileName = 'cbt_'.Str::uuid()->toString().'.'.$ext;
                        Storage::disk('public')->put('cbt_questions/'.$fileName, $imageBinary);

                        $imageUrl = '/storage/cbt_questions/'.$fileName;
                        $pHtml .= '<div class="my-3 text-center"><img src="'.$imageUrl.'" alt="Gambar Soal" class="max-h-80 max-w-full rounded-2xl shadow-md border border-slate-200 dark:border-slate-700 mx-auto inline-block object-contain" /></div>';
                        $hasImage = true;
                    }
                }
            }
        }

        $trimmedPlain = trim($pPlainText);
        if ($trimmedPlain === '' && ! $hasImage) {
            return null;
        }

        return [
            'text' => trim($pHtml),
            'plain_text' => $trimmedPlain,
            'has_image' => $hasImage,
            'is_table' => false,
        ];
    }

    /**
     * Parse elemen tabel OpenXML (<w:tbl>) menjadi tabel HTML responsif
     *
     * @param  array<string, string>  $rels
     * @return array{text: string, plain_text: string, has_image: bool, is_table: bool}|null
     */
    protected function parseTableNode(DOMElement $tbl, DOMXPath $xpath, ZipArchive $zip, array $rels): ?array
    {
        $trNodes = $xpath->query('./w:tr', $tbl);
        if (! $trNodes || $trNodes->length === 0) {
            return null;
        }

        $tableHtml = '<div class="my-3 overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">';
        $tableHtml .= '<table class="min-w-full text-left border-collapse text-xs sm:text-sm">';
        $tableHtml .= '<tbody>';

        $plainLines = [];
        $hasImageAny = false;
        $isFirstRow = true;

        foreach ($trNodes as $tr) {
            if (! $tr instanceof DOMElement) {
                continue;
            }

            $tcNodes = $xpath->query('./w:tc', $tr);
            if (! $tcNodes || $tcNodes->length === 0) {
                continue;
            }

            $tableHtml .= '<tr class="border-b border-slate-200 dark:border-slate-700/60 '.($isFirstRow ? 'bg-slate-100 dark:bg-slate-800/80 font-bold' : 'hover:bg-slate-50 dark:hover:bg-slate-800/30').'">';
            $rowPlainCells = [];

            foreach ($tcNodes as $tc) {
                if (! $tc instanceof DOMElement) {
                    continue;
                }

                $gridSpanNode = $xpath->query('./w:tcPr/w:gridSpan', $tc)->item(0);
                $colspan = $gridSpanNode instanceof DOMElement ? (int) $gridSpanNode->getAttribute('w:val') : 1;
                $colspanAttr = $colspan > 1 ? ' colspan="'.$colspan.'"' : '';

                $pInsideCell = $xpath->query('./w:p', $tc);
                $cellHtmlParts = [];
                $cellPlainParts = [];

                if ($pInsideCell && $pInsideCell->length > 0) {
                    foreach ($pInsideCell as $cellP) {
                        if ($cellP instanceof DOMElement) {
                            $parsedP = $this->parseParagraphNode($cellP, $xpath, $zip, $rels);
                            if ($parsedP) {
                                if ($parsedP['text'] !== '') {
                                    $cellHtmlParts[] = $parsedP['text'];
                                }
                                if ($parsedP['plain_text'] !== '') {
                                    $cellPlainParts[] = $parsedP['plain_text'];
                                }
                                if ($parsedP['has_image']) {
                                    $hasImageAny = true;
                                }
                            }
                        }
                    }
                }

                $cellContent = implode('<br/>', $cellHtmlParts);
                $cellPlain = implode(' ', $cellPlainParts);
                $rowPlainCells[] = $cellPlain;

                $tag = $isFirstRow ? 'th' : 'td';
                $extraClass = $isFirstRow
                    ? 'px-3 py-2 border border-slate-200 dark:border-slate-700 font-semibold text-slate-800 dark:text-slate-200'
                    : 'px-3 py-2 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300';

                $tableHtml .= '<'.$tag.$colspanAttr.' class="'.$extraClass.'">'.($cellContent !== '' ? $cellContent : '&nbsp;').'</'.$tag.'>';
            }

            $tableHtml .= '</tr>';
            $plainLines[] = implode(' | ', $rowPlainCells);
            $isFirstRow = false;
        }

        $tableHtml .= '</tbody></table></div>';

        return [
            'text' => $tableHtml,
            'plain_text' => implode("\n", $plainLines),
            'has_image' => $hasImageAny,
            'is_table' => true,
        ];
    }

    /**
     * Susun baris-baris paragraf menjadi struktur data butir soal CBT
     *
     * @param  array<int, array{text: string, plain_text: string, has_image: bool}>  $paragraphs
     * @return array<int, array<string, mixed>>
     */
    protected function structureQuestions(array $paragraphs): array
    {
        $questions = [];
        $currentQ = null;
        $currentOptionKey = null;

        foreach ($paragraphs as $p) {
            $plain = $p['plain_text'];
            $html = $p['text'];

            // 0. Jika elemen adalah tabel HTML, masukkan langsung ke konten soal atau opsi aktif
            if (! empty($p['is_table'])) {
                if ($currentQ !== null) {
                    if ($currentOptionKey !== null && isset($currentQ['options'][$currentOptionKey])) {
                        $currentQ['options'][$currentOptionKey]['text_parts'][] = $html;
                    } else {
                        $currentQ['question_html_parts'][] = $html;
                    }
                }

                continue;
            }

            // 1. Cek apakah ini awal butir soal baru: contoh "1.", "01."
            if (preg_match('/^(\d+)[\.]\s*(.*)$/su', $plain, $matches)) {
                if ($currentQ !== null) {
                    $questions[] = $this->finalizeQuestion($currentQ);
                }

                $qNumber = (int) $matches[1];
                $initText = trim($matches[2]);

                // Buang awalan nomor dari HTML agar tidak dobel nomor
                $cleanHtml = preg_replace('/^\d+[\.]\s*/u', '', $html);

                $currentQ = [
                    'number' => $qNumber,
                    'question_html_parts' => [$cleanHtml ?: $initText],
                    'options' => [],
                    'correct_answer' => null,
                    'explanation' => null,
                    'score_weight' => 10,
                    'question_type' => 'multiple_choice',
                ];
                $currentOptionKey = null;

                continue;
            }

            if ($currentQ === null) {
                // Baris sebelum nomor soal pertama (misal judul naskah), abaikan
                continue;
            }

            // 2. Cek apakah baris ini adalah Kunci Jawaban: contoh "Kunci: B", "Kunci: A, C", "Kunci: Benar", "Kunci: Nusantara", "Kunci: 1-B, 2-A"
            if (preg_match('/^(?:kunci(?:\s*jawaban)?|jawaban(?:\s*benar)?|ans(?:wer)?)\s*[:\-=]\s*(.+)$/iu', $plain, $matches)) {
                $currentQ['correct_answer'] = trim($matches[1]);
                $currentOptionKey = null;

                continue;
            }

            // 3. Cek apakah baris ini adalah Bobot Nilai: contoh "Bobot: 15"
            if (preg_match('/^bobot\s*[:\-=]\s*(\d+)/iu', $plain, $matches)) {
                $currentQ['score_weight'] = (int) $matches[1];
                $currentOptionKey = null;

                continue;
            }

            // 4. Cek apakah baris ini adalah Pembahasan: contoh "Pembahasan: ..."
            if (preg_match('/^(?:pembahasan|penjelasan|keterangan)\s*[:\-=]\s*(.*)$/iu', $plain, $matches)) {
                $currentQ['explanation'] = trim($matches[1]);
                $currentOptionKey = null;

                continue;
            }

            // 5. Cek apakah baris ini adalah Opsi Pilihan Ganda: contoh "A.", "B)", "A:"
            if (preg_match('/^([A-Ea-e])[\.\)\:]\s*(.*)$/su', $plain, $matches)) {
                $optKey = strtoupper($matches[1]);
                $cleanOptHtml = preg_replace('/^[A-Ea-e][\.\)\:]\s*/u', '', $html);

                $currentOptionKey = $optKey;
                $currentQ['options'][$optKey] = [
                    'key' => $optKey,
                    'text_parts' => [$cleanOptHtml],
                ];

                continue;
            }

            // 6. Baris lanjutan (bisa paragraf tambahan pertanyaan, atau lanjutan teks opsi)
            if ($currentOptionKey !== null && isset($currentQ['options'][$currentOptionKey])) {
                $currentQ['options'][$currentOptionKey]['text_parts'][] = $html;
            } else {
                $currentQ['question_html_parts'][] = $html;
            }
        }

        if ($currentQ !== null) {
            $questions[] = $this->finalizeQuestion($currentQ);
        }

        return $questions;
    }

    /**
     * Rapikan data butir soal sebelum disimpan
     *
     * @param  array<string, mixed>  $q
     * @return array<string, mixed>
     */
    protected function finalizeQuestion(array $q): array
    {
        $questionText = trim(implode('<br/>', $q['question_html_parts']));

        $formattedOptions = [];
        foreach ($q['options'] as $key => $optData) {
            $formattedOptions[] = [
                'key' => $key,
                'text' => trim(implode('<br/>', $optData['text_parts'])),
            ];
        }

        $rawAnswer = trim((string) ($q['correct_answer'] ?? ''));
        $rawUpper = strtoupper($rawAnswer);

        // Deteksi tipe secara eksplisit dari kurung petunjuk di teks soal
        $explicitType = null;
        if (preg_match('/\((?:benar[\/\s]*salah|true[\/\s]*false|b[\/]s)\)/iu', $questionText)) {
            $explicitType = 'true_false';
        } elseif (preg_match('/\((?:jawaban\s*singkat|isian\s*singkat|short\s*answer)\)/iu', $questionText)) {
            $explicitType = 'short_answer';
        } elseif (preg_match('/\((?:menjodohkan|matching|pasangan)\)/iu', $questionText)) {
            $explicitType = 'matching';
        } elseif (preg_match('/\((?:uraian|essay)\)/iu', $questionText)) {
            $explicitType = 'essay';
        }

        // 1. Tipe Benar / Salah (True / False)
        // Hanya jika eksplisit (Benar/Salah), ATAU jika tidak ada opsi A-D dan kuncinya BENAR/SALAH/TRUE/FALSE
        $isTrueFalseKey = in_array($rawUpper, ['BENAR', 'SALAH', 'TRUE', 'FALSE'])
            || (empty($formattedOptions) && in_array($rawUpper, ['B', 'S']));

        if ($explicitType === 'true_false' || $isTrueFalseKey) {
            $questionType = 'true_false';
            $correctAnswer = in_array($rawUpper, ['BENAR', 'B', 'TRUE']) ? 'TRUE' : 'FALSE';
            $formattedOptions = [
                ['key' => 'TRUE', 'text' => 'BENAR (TRUE)'],
                ['key' => 'FALSE', 'text' => 'SALAH (FALSE)'],
            ];
        }
        // 2. Tipe Menjodohkan (Matching: e.g. "1-B, 2-A, 3-C")
        elseif ($explicitType === 'matching' || preg_match('/^\d+[\-:\>][A-Za-z0-9]/', $rawAnswer)) {
            $questionType = 'matching';
            $correctAnswer = $rawAnswer;
        }
        // 3. Tipe Jawaban Singkat (Short Answer: kata/angka tanpa opsi A-D)
        elseif ($explicitType === 'short_answer' || (empty($formattedOptions) && $rawAnswer !== '')) {
            $questionType = 'short_answer';
            $formattedOptions = null;
            $correctAnswer = $rawAnswer;
        }
        // 4. Tipe Uraian (Essay: tidak ada opsi dan tidak ada kunci singkat)
        elseif ($explicitType === 'essay' || (empty($formattedOptions) && $rawAnswer === '')) {
            $questionType = 'essay';
            $formattedOptions = null;
            $correctAnswer = $rawAnswer ?: null;
        }
        // 5. Tipe Pilihan Ganda (Tunggal & Kompleks)
        else {
            $questionType = 'multiple_choice';
            $rawKeys = explode(',', $rawUpper);
            $cleanKeys = [];
            foreach ($rawKeys as $k) {
                $k = trim($k);
                if ($k !== '') {
                    $cleanKeys[] = $k;
                }
            }
            $correctAnswer = ! empty($cleanKeys) ? implode(',', $cleanKeys) : ($formattedOptions[0]['key'] ?? 'A');
        }

        return [
            'number' => $q['number'],
            'question_text' => $questionText,
            'question_type' => $questionType,
            'options' => ! empty($formattedOptions) ? $formattedOptions : null,
            'correct_answer' => $correctAnswer,
            'explanation' => $q['explanation'],
            'score_weight' => $q['score_weight'] ?? 10,
        ];
    }

    /**
     * Buat template naskah Word (.docx) resmi yang siap diunduh oleh guru.
     */
    public function generateTemplateDocx(): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'cbt_tpl_').'.docx';

        $zip = new ZipArchive;
        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Gagal membuat berkas template Word sementara.');
        }

        // 1. [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
  <Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // 2. _rels/.rels
        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>';
        $zip->addFromString('_rels/.rels', $rootRels);

        // 3. word/_rels/document.xml.rels
        $docRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>';
        $zip->addFromString('word/_rels/document.xml.rels', $docRels);

        // 4. word/styles.xml
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:docDefaults>
    <w:rPrDefault>
      <w:rPr>
        <w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/>
        <w:sz w:val="24"/>
      </w:rPr>
    </w:rPrDefault>
  </w:docDefaults>
</w:styles>';
        $zip->addFromString('word/styles.xml', $styles);

        // 5. word/document.xml
        $documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
            xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
            xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">
  <w:body>
    <!-- Header Panduan -->
    <w:p>
      <w:r><w:rPr><w:b/><w:sz w:val="28"/><w:color w:val="1E3A8A"/></w:rPr><w:t>SISTEM INFORMASI SEKOLAH - TEMPLATE SOAL UJIAN CBT (.DOCX)</w:t></w:r>
    </w:p>
    <w:p>
      <w:r><w:rPr><w:i/><w:color w:val="475569"/></w:rPr><w:t>Petunjuk: Tulis nomor soal (1., 2.), opsi (A., B., C., D.), dan kunci jawaban (Kunci: X). Anda dapat menyisipkan gambar langsung di bawah soal (Insert > Picture).</w:t></w:r>
    </w:p>
    <w:p/>

    <!-- Soal No 1: Pilihan Ganda Biasa -->
    <w:p>
      <w:r><w:rPr><w:b/></w:rPr><w:t>1. Manakah dari perangkat berikut yang merupakan perangkat keras pemrosesan utama pada komputer?</w:t></w:r>
    </w:p>
    <w:p><w:r><w:t>A. Monitor</w:t></w:r></w:p>
    <w:p><w:r><w:t>B. Keyboard</w:t></w:r></w:p>
    <w:p><w:r><w:t>C. Central Processing Unit (CPU)</w:t></w:r></w:p>
    <w:p><w:r><w:t>D. Mouse Optik</w:t></w:r></w:p>
    <w:p>
      <w:r><w:rPr><w:b/><w:color w:val="15803D"/></w:rPr><w:t>Kunci: C</w:t></w:r>
    </w:p>
    <w:p><w:r><w:t>Bobot: 10</w:t></w:r></w:p>
    <w:p><w:r><w:i/><w:t>Pembahasan: CPU adalah otak pemrosesan utama instruksi komputer.</w:t></w:r></w:p>
    <w:p/>

    <!-- Soal No 2: Pilihan Ganda Kompleks (Jawaban Lebih Dari Satu) -->
    <w:p>
      <w:r><w:rPr><w:b/></w:rPr><w:t>2. Manakah komponen di bawah ini yang berfungsi sebagai media penyimpanan data sekunder permanen? (Pilihan Ganda Kompleks)</w:t></w:r>
    </w:p>
    <w:p><w:r><w:t>A. Solid State Drive (SSD)</w:t></w:r></w:p>
    <w:p><w:r><w:t>B. Random Access Memory (RAM)</w:t></w:r></w:p>
    <w:p><w:r><w:t>C. Hard Disk Drive (HDD)</w:t></w:r></w:p>
    <w:p><w:r><w:t>D. Cache L1</w:t></w:r></w:p>
    <w:p>
      <w:r><w:rPr><w:b/><w:color w:val="15803D"/></w:rPr><w:t>Kunci: A, C</w:t></w:r>
    </w:p>
    <w:p><w:r><w:t>Bobot: 10</w:t></w:r></w:p>
    <w:p/>

    <!-- Soal No 3: Soal dengan Gambar -->
    <w:p>
      <w:r><w:rPr><w:b/></w:rPr><w:t>3. Perhatikan diagram topologi jaringan di bawah ini! Sisipkan gambar diagram Anda di sini melalui menu Insert > Picture di Microsoft Word.</w:t></w:r>
    </w:p>
    <w:p><w:r><w:t>Topologi jaringan yang setiap nodenya terhubung ke satu kabel konsentrator utama disebut...</w:t></w:r></w:p>
    <w:p><w:r><w:t>A. Topologi Mesh</w:t></w:r></w:p>
    <w:p><w:r><w:t>B. Topologi Star</w:t></w:r></w:p>
    <w:p><w:r><w:t>C. Topologi Ring</w:t></w:r></w:p>
    <w:p><w:r><w:t>D. Topologi Bus</w:t></w:r></w:p>
    <w:p>
      <w:r><w:rPr><w:b/><w:color w:val="15803D"/></w:rPr><w:t>Kunci: B</w:t></w:r>
    </w:p>
    <w:p><w:r><w:t>Bobot: 10</w:t></w:r></w:p>
    <w:p/>

    <!-- Soal No 4: Soal dengan Tabel Asli Word (Insert > Table) -->
    <w:p>
      <w:r><w:rPr><w:b/></w:rPr><w:t>4. Perhatikan tabel data pengujian larutan dengan kertas lakmus berikut ini:</w:t></w:r>
    </w:p>
    <w:tbl>
      <w:tblPr>
        <w:tblBorders>
          <w:top w:val="single" w:sz="4" w:space="0" w:color="auto"/>
          <w:left w:val="single" w:sz="4" w:space="0" w:color="auto"/>
          <w:bottom w:val="single" w:sz="4" w:space="0" w:color="auto"/>
          <w:right w:val="single" w:sz="4" w:space="0" w:color="auto"/>
          <w:insideH w:val="single" w:sz="4" w:space="0" w:color="auto"/>
          <w:insideV w:val="single" w:sz="4" w:space="0" w:color="auto"/>
        </w:tblBorders>
      </w:tblPr>
      <w:tr>
        <w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Larutan</w:t></w:r></w:p></w:tc>
        <w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Warna Lakmus Merah</w:t></w:r></w:p></w:tc>
        <w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Warna Lakmus Biru</w:t></w:r></w:p></w:tc>
      </w:tr>
      <w:tr>
        <w:tc><w:p><w:r><w:t>P</w:t></w:r></w:p></w:tc>
        <w:tc><w:p><w:r><w:t>Merah</w:t></w:r></w:p></w:tc>
        <w:tc><w:p><w:r><w:t>Merah</w:t></w:r></w:p></w:tc>
      </w:tr>
      <w:tr>
        <w:tc><w:p><w:r><w:t>Q</w:t></w:r></w:p></w:tc>
        <w:tc><w:p><w:r><w:t>Biru</w:t></w:r></w:p></w:tc>
        <w:tc><w:p><w:r><w:t>Biru</w:t></w:r></w:p></w:tc>
      </w:tr>
    </w:tbl>
    <w:p><w:r><w:t>Berdasarkan data tabel di atas, larutan yang bersifat asam adalah...</w:t></w:r></w:p>
    <w:p><w:r><w:t>A. Larutan P</w:t></w:r></w:p>
    <w:p><w:r><w:t>B. Larutan Q</w:t></w:r></w:p>
    <w:p><w:r><w:t>C. Larutan P dan Q</w:t></w:r></w:p>
    <w:p><w:r><w:t>D. Tidak ada larutan yang asam</w:t></w:r></w:p>
    <w:p>
      <w:r><w:rPr><w:b/><w:color w:val="15803D"/></w:rPr><w:t>Kunci: A</w:t></w:r>
    </w:p>
    <w:p><w:r><w:t>Bobot: 10</w:t></w:r></w:p>
    <w:p><w:r><w:i/><w:t>Pembahasan: Larutan asam memerahkan lakmus biru dan tetap merah pada lakmus merah.</w:t></w:r></w:p>
    <w:p/>

    <!-- Soal No 5: Benar / Salah (True / False) -->
    <w:p>
      <w:r><w:rPr><w:b/></w:rPr><w:t>5. Monitor dan proyektor merupakan perangkat keras pemrosesan data (processing device) pada sistem komputer. (Benar/Salah)</w:t></w:r>
    </w:p>
    <w:p>
      <w:r><w:rPr><w:b/><w:color w:val="15803D"/></w:rPr><w:t>Kunci: Salah</w:t></w:r>
    </w:p>
    <w:p><w:r><w:t>Bobot: 10</w:t></w:r></w:p>
    <w:p><w:r><w:i/><w:t>Pembahasan: Monitor dan proyektor adalah perangkat keluaran (output device), bukan pemrosesan.</w:t></w:r></w:p>
    <w:p/>

    <!-- Soal No 6: Jawaban Singkat (Short Answer) -->
    <w:p>
      <w:r><w:rPr><w:b/></w:rPr><w:t>6. Nama ibu kota baru negara Republik Indonesia yang berlokasi di Provinsi Kalimantan Timur adalah... (Jawaban Singkat)</w:t></w:r>
    </w:p>
    <w:p>
      <w:r><w:rPr><w:b/><w:color w:val="15803D"/></w:rPr><w:t>Kunci: Nusantara / IKN</w:t></w:r>
    </w:p>
    <w:p><w:r><w:t>Bobot: 10</w:t></w:r></w:p>
    <w:p><w:r><w:i/><w:t>Pembahasan: Ibu Kota Nusantara (IKN) merupakan pusat pemerintahan baru Indonesia.</w:t></w:r></w:p>
    <w:p/>

    <!-- Soal No 7: Menjodohkan (Matching) -->
    <w:p>
      <w:r><w:rPr><w:b/></w:rPr><w:t>7. Jodohkanlah perangkat komputer di Kolom A dengan kategori fungsinya di Kolom B berikut ini: (Menjodohkan)</w:t></w:r>
    </w:p>
    <w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Kolom A (Pernyataan):</w:t></w:r></w:p>
    <w:p><w:r><w:t>[1] Central Processing Unit (CPU)</w:t></w:r></w:p>
    <w:p><w:r><w:t>[2] Random Access Memory (RAM)</w:t></w:r></w:p>
    <w:p><w:r><w:t>[3] Keyboard Mekanikal</w:t></w:r></w:p>
    <w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Kolom B (Pilihan):</w:t></w:r></w:p>
    <w:p><w:r><w:t>[A] Memori penyimpanan sementara kecepatan tinggi</w:t></w:r></w:p>
    <w:p><w:r><w:t>[B] Unit pemrosesan instruksi utama komputer</w:t></w:r></w:p>
    <w:p><w:r><w:t>[C] Perangkat masukan teks dan tombol perintah</w:t></w:r></w:p>
    <w:p>
      <w:r><w:rPr><w:b/><w:color w:val="15803D"/></w:rPr><w:t>Kunci: 1-B, 2-A, 3-C</w:t></w:r>
    </w:p>
    <w:p><w:r><w:t>Bobot: 15</w:t></w:r></w:p>
    <w:p/>

    <!-- Soal No 8: Uraian (Essay) -->
    <w:p>
      <w:r><w:rPr><w:b/></w:rPr><w:t>8. Jelaskan perbedaan mendasar antara perangkat keras (hardware) dan perangkat lunak (software) beserta masing-masing contohnya! (Uraian)</w:t></w:r>
    </w:p>
    <w:p><w:r><w:t>Bobot: 15</w:t></w:r></w:p>
    <w:p><w:r><w:i/><w:t>Pembahasan: Hardware adalah komponen fisik berwujud nyata (CPU, RAM, dsb.), sedangkan software adalah instruksi program digital tanpa wujud fisik (Sistem Operasi, Aplikasi, dsb.).</w:t></w:r></w:p>
    <w:p/>

    <w:sectPr/>
  </w:body>
</w:document>';
        $zip->addFromString('word/document.xml', $documentXml);
        $zip->close();

        return $tempFile;
    }
}
