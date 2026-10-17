<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * ห้าม seed — โปรเจกต์นี้ใช้ DB เดิมของเว็บจริง (ไม่มี migration/seed ของ Laravel)
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->error('jl ใช้ DB เดิม — ไม่มี seeder (ดู docs/CONVENTIONS.md)');
    }
}
