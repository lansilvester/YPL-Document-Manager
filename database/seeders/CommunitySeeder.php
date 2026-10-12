<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * BAB 3/17: unit organisasi YPL + kontak PIC (publik) agar direktori
 * komunitas dan pencarian kontak hidup sejak awal. Idempotent.
 */
class CommunitySeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['Direktorat Umum', 'Tata kelola, kepegawaian, dan layanan umum yayasan.', [
                ['Nelly Rantung', 'Direktur Umum', 'umum@lokon.or.id', '0431-351000'],
            ]],
            ['Direktorat Keuangan', 'Pengelolaan keuangan, pengadaan, dan aset.', [
                ['Yohanis Manopo', 'Kepala Pengadaan', 'pengadaan@lokon.or.id', '0431-351001'],
                ['Maria Walangitan', 'Staf Keuangan', 'keuangan@lokon.or.id', '0431-351002'],
            ]],
            ['Direktorat Akademik', 'Kurikulum, mutu akademik, dan pengembangan guru.', [
                ['Pdt. Daniel Sombouw', 'Direktur Akademik', 'akademik@lokon.or.id', '0431-351003'],
            ]],
            ['Kompleks Asrama', 'Pembinaan dan pengelolaan asrama putra dan putri.', [
                ['Samuel Kaseger', 'Kepala Asrama', 'asrama@lokon.or.id', '0431-351004'],
            ]],
        ];

        foreach ($units as $order => [$name, $desc, $contacts]) {
            $unit = Unit::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'description' => $desc, 'sort_order' => $order]
            );

            foreach ($contacts as $i => [$cname, $position, $email, $phone]) {
                Contact::updateOrCreate(
                    ['unit_id' => $unit->id, 'name' => $cname],
                    ['position' => $position, 'email' => $email, 'phone' => $phone, 'sort_order' => $i]
                );
            }
        }

        $this->command->info('Unit & kontak komunitas YPL selesai dimuat.');
    }
}
