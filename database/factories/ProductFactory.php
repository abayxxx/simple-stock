<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition()
    {
        return [
            'nama' => $this->faker->words(3, true),
            'merk' => $this->faker->company,
            'satuan_kecil' => 'pcs',
            'isi_satuan_kecil' => 1,
            'satuan_sedang' => 'dus',
            'isi_satuan_sedang' => $this->faker->numberBetween(5, 24),
            'satuan_besar' => 'koli',
            'isi_satuan_besar' => $this->faker->numberBetween(2, 10),
            'satuan_massa' => 'kg',
            'isi_satuan_massa' => $this->faker->randomFloat(2, 0.1, 25),
            'catatan' => $this->faker->optional()->sentence,
            'hpp_bruto_kecil' => $this->faker->randomFloat(2, 1000, 100000),
            'hpp_bruto_besar' => $this->faker->randomFloat(2, 5000, 500000),
            'diskon_hpp_1' => $this->faker->numberBetween(0, 20),
            'diskon_hpp_2' => $this->faker->numberBetween(0, 20),
            'diskon_hpp_3' => $this->faker->numberBetween(0, 20),
            'diskon_hpp_4' => $this->faker->numberBetween(0, 20),
            'diskon_hpp_5' => $this->faker->numberBetween(0, 20),
            'harga_umum' => $this->faker->randomFloat(2, 10000, 500000),
            'diskon_harga_1' => $this->faker->numberBetween(0, 30),
            'diskon_harga_2' => $this->faker->numberBetween(0, 30),
            'diskon_harga_3' => $this->faker->numberBetween(0, 30),
            'diskon_harga_4' => $this->faker->numberBetween(0, 30),
            'diskon_harga_5' => $this->faker->numberBetween(0, 30),
        ];
    }
}
