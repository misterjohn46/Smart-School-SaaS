<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleName: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Headmaster = 'kepala_sekolah';
    case Teacher = 'guru';
    case Counselor = 'guru_bk';
    case Treasurer = 'bendahara';
    case Operator = 'operator';
    case Staff = 'staff';
    case Librarian = 'pustakawan';
    case Student = 'siswa';
    case Parent = 'orang_tua';
    case Candidate = 'calon_siswa';
    case Canteen = 'kantin';
    case CanteenVendor = 'vendor_kantin';
    case CanteenCashier = 'kasir_kantin';
    case User = 'user';
}
