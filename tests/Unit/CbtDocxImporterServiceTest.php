<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\AcademicYear;
use App\Models\CbtBank;
use App\Models\Subject;
use App\Models\User;
use App\Services\CbtDocxImporterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CbtDocxImporterServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_generate_and_import_docx_template(): void
    {
        $teacher = User::factory()->create();
        $subject = Subject::create([
            'code' => 'TIK-01',
            'name' => 'Informatika',
            'kkm' => 75,
            'type' => 'umum',
            'is_active' => true,
        ]);

        $bank = CbtBank::create([
            'title' => 'Bank Soal Simulasi PAS',
            'subject_id' => $subject->id,
            'teacher_user_id' => $teacher->id,
            'passing_score' => 75,
            'total_questions' => 0,
            'is_active' => true,
        ]);

        $service = new CbtDocxImporterService();
        $templatePath = $service->generateTemplateDocx();

        $this->assertFileExists($templatePath);
        $this->assertGreaterThan(500, filesize($templatePath));

        $result = $service->import($templatePath, $bank);

        $this->assertSame(8, $result['total_imported']);
        $this->assertCount(8, $bank->fresh()->questions);

        // Periksa soal no 1 (Pilihan Ganda Tunggal)
        $q1 = $bank->questions()->where('score_weight', 10)->first();
        $this->assertNotNull($q1);
        $this->assertStringContainsString('Central Processing Unit', $q1->question_text . json_encode($q1->options));
        $this->assertSame('C', $q1->correct_answer);

        // Periksa soal no 2 (Pilihan Ganda Kompleks: Kunci A, C)
        $q2 = $bank->questions()->where('correct_answer', 'A,C')->first();
        $this->assertNotNull($q2);
        $this->assertStringContainsString('Solid State Drive', json_encode($q2->options));

        // Periksa soal no 4 (Soal dengan Tabel Asli Word)
        $q4 = $bank->questions()->where('question_text', 'like', '%<table%')->first();
        $this->assertNotNull($q4);
        $this->assertStringContainsString('Warna Lakmus Merah', $q4->question_text);
        $this->assertStringContainsString('Larutan P', json_encode($q4->options));
        $this->assertSame('A', $q4->correct_answer);

        // Periksa soal no 5 (Benar / Salah)
        $q5 = $bank->questions()->where('question_type', 'true_false')->first();
        $this->assertNotNull($q5);
        $this->assertSame('FALSE', $q5->correct_answer);

        // Periksa soal no 6 (Jawaban Singkat)
        $q6 = $bank->questions()->where('question_type', 'short_answer')->first();
        $this->assertNotNull($q6);
        $this->assertStringContainsString('Nusantara', $q6->correct_answer);

        // Periksa soal no 7 (Menjodohkan)
        $q7 = $bank->questions()->where('question_type', 'matching')->first();
        $this->assertNotNull($q7);
        $this->assertStringContainsString('1-B', $q7->correct_answer);

        // Periksa soal no 8 (Uraian / Essay)
        $q8 = $bank->questions()->where('question_type', 'essay')->first();
        $this->assertNotNull($q8);
        $this->assertStringContainsString('perbedaan mendasar', $q8->question_text);

        // Bersihkan berkas sementara
        @unlink($templatePath);
    }

    public function test_can_extract_images_from_docx_file(): void
    {
        $teacher = User::factory()->create();
        $subject = Subject::create([
            'code' => 'IPA-01',
            'name' => 'Ilmu Pengetahuan Alam',
            'kkm' => 75,
            'type' => 'umum',
            'is_active' => true,
        ]);

        $bank = CbtBank::create([
            'title' => 'Bank Soal Biologi Bergambar',
            'subject_id' => $subject->id,
            'teacher_user_id' => $teacher->id,
            'passing_score' => 75,
            'total_questions' => 0,
            'is_active' => true,
        ]);

        // Buat file docx dengan berkas gambar tiruan (1x1 pixel PNG)
        $pngBinary = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        $docxPath = tempnam(sys_get_temp_dir(), 'test_img_') . '.docx';

        $zip = new \ZipArchive();
        $zip->open($docxPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Default Extension="png" ContentType="image/png"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
        $zip->addFromString('word/_rels/document.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rIdImg1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/diagram.png"/></Relationships>');
        $zip->addFromString('word/media/diagram.png', $pngBinary);

        $documentXml = '<?xml version="1.0" encoding="UTF-8"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
            xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
            xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">
  <w:body>
    <w:p>
      <w:r><w:t>1. Perhatikan gambar sel di bawah ini!</w:t></w:r>
      <w:r>
        <w:drawing>
          <a:blip r:embed="rIdImg1"/>
        </w:drawing>
      </w:r>
      <w:r><w:t>Bagian yang ditunjuk berfungsi sebagai pusat kendali sel.</w:t></w:r>
    </w:p>
    <w:p><w:r><w:t>A. Mitokondria</w:t></w:r></w:p>
    <w:p><w:r><w:t>B. Nukleus</w:t></w:r></w:p>
    <w:p><w:r><w:t>C. Ribosom</w:t></w:r></w:p>
    <w:p><w:r><w:t>D. Vakuola</w:t></w:r></w:p>
    <w:p><w:r><w:t>Kunci: B</w:t></w:r></w:p>
    <w:p><w:r><w:t>Bobot: 10</w:t></w:r></w:p>
  </w:body>
</w:document>';
        $zip->addFromString('word/document.xml', $documentXml);
        $zip->close();

        $service = new CbtDocxImporterService();
        $res = $service->import($docxPath, $bank);

        $this->assertSame(1, $res['total_imported']);
        $question = $bank->questions()->first();
        $this->assertNotNull($question);
        $this->assertStringContainsString('<img src=', $question->question_text);
        $this->assertStringContainsString('cbt_', $question->question_text);
        $this->assertSame('B', $question->correct_answer);

        @unlink($docxPath);
    }
}
