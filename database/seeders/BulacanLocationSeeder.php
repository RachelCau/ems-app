<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\City;
use App\Models\Province;
use Illuminate\Database\Seeder;

class BulacanLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Bulacan Province
        $province = Province::create([
            'name' => 'Bulacan',
            'region_code' => '03', // Region III (Central Luzon)
            'code' => 'BUL',
        ]);

        // Cities/Municipalities in Bulacan
        $cities = [
            [
                'name' => 'Malolos',
                'is_city' => true,
                'code' => 'BUL-MAL',
                'barangays' => [
                    'Atlag', 'Babatnin', 'Balayong', 'Balite', 'Bangkal', 'Barihan', 
                    'Bulihan', 'Caingin', 'Calero', 'Canalate', 'Catmon', 'Cofradia',
                    'Dakila', 'Guinhawa', 'Ligas', 'Liyang', 'Longos', 'Look 1st',
                    'Look 2nd', 'Lugam', 'Mabolo', 'Mapulang Lupa', 'Masile', 'Mojon',
                    'Namayan', 'Niugan', 'Pamarawan', 'Panasahan', 'Pinagbakahan', 'San Agustin',
                    'San Gabriel', 'San Juan', 'San Pablo', 'San Vicente', 'Santiago', 'Santo Cristo',
                    'Santo Niño', 'Santo Rosario', 'Santisima Trinidad', 'Sumapang Bata', 'Sumapang Matanda',
                    'Taal', 'Tikay'
                ]
            ],
            [
                'name' => 'Meycauayan',
                'is_city' => true,
                'code' => 'BUL-MEY',
                'barangays' => [
                    'Bagbaguin', 'Bahay Pare', 'Bancal', 'Banga', 'Bayugo', 'Caingin',
                    'Calvario', 'Camalig', 'Hulo', 'Iba', 'Langka', 'Lawa',
                    'Libtong', 'Liputan', 'Longos', 'Malhacan', 'Pajo', 'Pandayan',
                    'Pantoc', 'Perez', 'Poblacion', 'Saint Francis', 'Saluysoy', 'Tugatog',
                    'Ubihan', 'Zamora'
                ]
            ],
            [
                'name' => 'San Jose del Monte',
                'is_city' => true,
                'code' => 'BUL-SJDM',
                'barangays' => [
                    'Bagong Buhay I', 'Bagong Buhay II', 'Bagong Buhay III', 'Citrus', 'Ciudad Real',
                    'Dulong Bayan', 'Fatima', 'Fatima II', 'Fatima III', 'Fatima IV', 'Fatima V',
                    'Francisco Homes', 'Graceville', 'Gumaoc Central', 'Gumaoc East', 'Gumaoc West',
                    'Kaybanban', 'Kaypian', 'Lawang Pari', 'Maharlika', 'Minuyan I', 'Minuyan II',
                    'Minuyan III', 'Minuyan IV', 'Minuyan V', 'Minuyan Proper', 'Muzon', 'Paradise III',
                    'Poblacion', 'Poblacion I', 'Saint Martin', 'San Manuel', 'San Martin I',
                    'San Martin II', 'San Martin III', 'San Martin IV', 'San Pedro', 'San Rafael I', 
                    'San Rafael II', 'San Rafael III', 'San Rafael IV', 'San Rafael V', 'San Roque',
                    'Santa Cruz I', 'Santa Cruz II', 'Santa Cruz III', 'Santa Cruz IV', 'Santa Cruz V',
                    'Santo Cristo', 'Santo Niño', 'Santo Niño II', 'Sapang Palay', 'Tungkong Mangga'
                ]
            ],
            [
                'name' => 'Balagtas',
                'is_city' => false,
                'code' => 'BUL-BAL',
                'barangays' => [
                    'Borol 1st', 'Borol 2nd', 'Dalig', 'Longos', 'Panginay',
                    'Pulong Gubat', 'San Juan', 'Santol', 'Wawa'
                ]
            ],
            [
                'name' => 'Baliuag',
                'is_city' => false,
                'code' => 'BUL-BAL2',
                'barangays' => [
                    'Bagong Nayon', 'Barangca', 'Calantipay', 'Catulinan', 'Concepcion',
                    'Hinukay', 'Makinabang', 'Matangtubig', 'Pagala', 'Paitan', 'Pinagbarilan',
                    'Poblacion', 'San Jose', 'San Roque', 'Santo Cristo', 'Santo Niño',
                    'Subic', 'Sulivan', 'Tangos', 'Tarcan', 'Tiaong', 'Tibag', 'Tilapayong',
                    'Timbao', 'Virgen delas Flores'
                ]
            ],
            [
                'name' => 'Bocaue',
                'is_city' => false,
                'code' => 'BUL-BOC',
                'barangays' => [
                    'Antipona', 'Bagumbayan', 'Bambang', 'Batia', 'Biñang 1st', 'Biñang 2nd',
                    'Boboc', 'Bolacan', 'Bunlo', 'Bundukan', 'Caingin', 'Duhat', 'Igulot',
                    'Lolomboy', 'Poblacion', 'Sulucan', 'Taal', 'Tambobong', 'Turo'
                ]
            ],
            [
                'name' => 'Bulakan',
                'is_city' => false,
                'code' => 'BUL-BLK',
                'barangays' => [
                    'Bagumbayan', 'Balubad', 'Bambang', 'Matungao', 'Maysantol', 'Perez',
                    'Pitpitan', 'San Francisco', 'San Jose', 'San Nicolas', 'Santa Ana',
                    'Santa Ines', 'Taliptip'
                ]
            ],
            [
                'name' => 'Bustos',
                'is_city' => false,
                'code' => 'BUL-BUS',
                'barangays' => [
                    'Bonga Mayor', 'Bonga Menor', 'Buisan', 'Cambaog', 'Catacte',
                    'Liciada', 'Malamig', 'Malawak', 'Poblacion', 'San Pedro',
                    'Talampas', 'Tanawan', 'Tibagan'
                ]
            ],
            [
                'name' => 'Calumpit',
                'is_city' => false,
                'code' => 'BUL-CAL',
                'barangays' => [
                    'Balungao', 'Buguion', 'Bulusan', 'Calizon', 'Calumpang',
                    'Corazon', 'Frances', 'Gatbuca', 'Gugo', 'Iba Este', 'Iba O\'Este',
                    'Longos', 'Meysulao', 'Meyto', 'Palimbang', 'Panducot', 'Pio Cruzcosa',
                    'Poblacion', 'Pungo', 'San Jose', 'San Marcos', 'San Miguel',
                    'Santa Lucia', 'Sapang Bayan', 'Sucol', 'Tibaguin'
                ]
            ],
            [
                'name' => 'DRT',
                'is_city' => false,
                'code' => 'BUL-DRT',
                'barangays' => [
                    'Bayabas', 'Bilog', 'Bintog', 'Bulak', 'Camachile', 'Camachin',
                    'Caniogan', 'Catacte', 'Diliman I', 'Diliman II', 'Kalawakan',
                    'Karahume', 'Kabatis', 'Mabolo', 'Mamburao', 'Matangtubig',
                    'Pinambaran', 'Tabang'
                ]
            ],
            [
                'name' => 'Guiguinto',
                'is_city' => false,
                'code' => 'BUL-GUI',
                'barangays' => [
                    'Cutcot', 'Daungan', 'Ilang-Ilang', 'Malis', 'Panginay', 'Poblacion',
                    'Pritil', 'Pulong Gubat', 'Saint Cruz', 'Santa Cruz', 'Santa Rita',
                    'Tabang', 'Tabe', 'Tiaong', 'Tuktukan'
                ]
            ],
            [
                'name' => 'Hagonoy',
                'is_city' => false,
                'code' => 'BUL-HAG',
                'barangays' => [
                    'Abulalas', 'Carillo', 'Iba', 'Mercado', 'Palapat', 'Pugad',
                    'San Agustin', 'San Isidro', 'San Jose', 'San Juan',
                    'San Miguel', 'San Nicolas', 'San Pablo', 'San Pascual',
                    'San Pedro', 'San Roque', 'San Sebastian', 'Santa Cruz',
                    'Santa Elena', 'Santa Monica', 'Santo Ni+APFzo', 'Santo Rosario',
                    'Sagrada Familia', 'Tampok', 'Tibaguin'
                ]
            ],
            [
                'name' => 'Marilao',
                'is_city' => false,
                'code' => 'BUL-MAR',
                'barangays' => [
                    'Abangan Norte', 'Abangan Sur', 'Ibayo', 'Lambakin', 'Lias',
                    'Loma de Gato', 'Nagbalon', 'Patubig', 'Poblacion I',
                    'Poblacion II', 'Prenza I', 'Prenza II', 'Santa Rosa I',
                    'Santa Rosa II', 'Saog', 'Tabing Ilog'
                ]
            ],
            [
                'name' => 'Norzagaray',
                'is_city' => false,
                'code' => 'BUL-NOR',
                'barangays' => [
                    'Bangkal', 'Baraka', 'Bigte', 'Bitungol', 'Friendship Village',
                    'Matictic', 'Minuyan', 'Partida', 'Pinagtulayan', 'Poblacion',
                    'San Lorenzo', 'San Mateo'
                ]
            ],
            [
                'name' => 'Obando',
                'is_city' => false,
                'code' => 'BUL-OBA',
                'barangays' => [
                    'Binuangan', 'Catanghalan', 'Hulo', 'Lawa', 'Paco',
                    'Pag-asa', 'Paliwas', 'Panghulo', 'Salambao', 'San Pascual',
                    'Tawiran'
                ]
            ],
            [
                'name' => 'Pandi',
                'is_city' => false,
                'code' => 'BUL-PAN',
                'barangays' => [
                    'Bagbaguin', 'Bagong Barrio', 'Bunlo', 'Bunsuran I', 'Bunsuran II',
                    'Bunsuran III', 'Cacarong Bata', 'Cacarong Matanda', 'Cupang',
                    'Malibong Bata', 'Malibong Matanda', 'Mapulang Lupa', 'Masagana',
                    'Masuso', 'Pinagkuartelan', 'Poblacion', 'Real de Cacarong',
                    'Siling Bata', 'Siling Matanda', 'Apiahan'
                ]
            ],
            [
                'name' => 'Paombong',
                'is_city' => false,
                'code' => 'BUL-PAO',
                'barangays' => [
                    'Binakod', 'Bulihan', 'Kapitangan', 'Malhacan', 'Masukol',
                    'Pinalagdan', 'Poblacion', 'San Isidro I', 'San Isidro II',
                    'San Jose', 'San Roque', 'San Vicente', 'Santa Cruz',
                    'Santo Ni+APFzo'
                ]
            ],
            [
                'name' => 'Plaridel',
                'is_city' => false,
                'code' => 'BUL-PLA',
                'barangays' => [
                    'Agnaya', 'Bagong Silang', 'Banga I', 'Banga II', 'Bintog',
                    'Bulihan', 'Culianin', 'Dampol', 'Lagundi', 'Lalangan',
                    'Lumang Bayan', 'Parulan', 'Poblacion', 'Rueda', 'San Jose',
                    'Santa Ines', 'Santo Ni+APFzo', 'Sipat', 'Tabang'
                ]
            ],
            [
                'name' => 'Pulilan',
                'is_city' => false,
                'code' => 'BUL-PUL',
                'barangays' => [
                    'Balatong A', 'Balatong B', 'Cutcot', 'Dampol', 'Dulong Malabon',
                    'Inaon', 'Lumbac', 'Longos', 'Paltao', 'Poblacion', 'Penabatan',
                    'Santa Peregrina', 'Santo Cristo', 'Tabon', 'Tibag', 'Taal',
                    'Tinejero'
                ]
            ],
            [
                'name' => 'San Ildefonso',
                'is_city' => false,
                'code' => 'BUL-SIL',
                'barangays' => [
                    'Akle', 'Alagao', 'Anyatam', 'Bagong Barrio', 'Balite', 'Basuit',
                    'Bubulong Malaki', 'Bubulong Munti', 'Buhol na Mangga', 'Bulusukan',
                    'Calasag', 'Calawitan', 'Casalat', 'Garlang', 'Gabihan', 'Lapnit',
                    'Maasin', 'Maasusuin', 'Malipampang', 'Matimbubong', 'Nabaong Garlang',
                    'Palapala', 'Pangpatubig', 'Pasong Bangkal', 'Pinaod', 'Poblacion',
                    'Pulong Tamo', 'San Juan', 'Santa Monica', 'Sapang Dayap', 'Sapang Putol',
                    'Sumandig', 'Telepatio', 'Upig', 'Umpucan'
                ]
            ],
            [
                'name' => 'San Miguel',
                'is_city' => false,
                'code' => 'BUL-SMG',
                'barangays' => [
                    'Bagong Pag-asa', 'Bagong Silang', 'Balaong', 'Balite', 'Baritan',
                    'Batasan Bata', 'Batasan Matanda', 'Biak-na-Bato', 'Biclat',
                    'Buliran', 'Bulualto', 'Calumpang', 'Cambio', 'Camias', 'Ilog-Bulo',
                    'King Kabayo', 'Lambakin', 'Magmarale', 'Malibay', 'Mandile',
                    'Pacalag', 'Paliwasan', 'Partida', 'Pinambaran', 'Poblacion',
                    'Pulong Bayabas', 'Salacot', 'Salapungan', 'San Agustin', 'San Jose',
                    'San Juan', 'San Vicente', 'Santa Ines', 'Santa Lucia', 'Santa Rita',
                    'Santa Rita Bata', 'Santa Rita Matanda', 'Sibul', 'Tartaro', 'Tibagan'
                ]
            ],
            [
                'name' => 'San Rafael',
                'is_city' => false,
                'code' => 'BUL-SRA',
                'barangays' => [
                    'Banca-banca', 'Caingin', 'Capihan', 'Cruz na Daan', 'Dagat-dagatan',
                    'Diliman I', 'Diliman II', 'Libis', 'Lico', 'Maasim', 'Mabalas-balas',
                    'Maguinao', 'Maronquillo', 'Paco', 'Pansumaloc', 'Pasong Bangkal',
                    'Pasong Callos', 'Pasong Intsik', 'Poblacion', 'Panligawan',
                    'Putol', 'Salapungan', 'Sampaloc', 'San Agustin', 'San Rafael',
                    'Sapang Pahalang', 'Talacsan', 'Tambubong', 'Tukod', 'Ulingao'
                ]
            ],
            [
                'name' => 'Santa Maria',
                'is_city' => false,
                'code' => 'BUL-SMA',
                'barangays' => [
                    'Bagbaguin', 'Balasing', 'Buenavista', 'Bulac', 'Camangyanan',
                    'Catmon', 'Caysio', 'Guyong', 'Lalakhan', 'Mag-asawang Sapa',
                    'Mahabang Parang', 'Parada', 'Poblacion', 'Pulong Buhangin',
                    'San Gabriel', 'San Jose Patag', 'San Vicente', 'Santa Clara',
                    'Santa Cruz', 'Silangan', 'Tabing Bakod', 'Tanggal', 'Tumana'
                ]
            ]
        ];

        foreach ($cities as $cityData) {
            $barangayNames = $cityData['barangays'];
            unset($cityData['barangays']);
            
            $city = City::create([
                'province_id' => $province->id,
                'name' => $cityData['name'],
                'is_city' => $cityData['is_city'],
                'code' => $cityData['code'],
            ]);
            
            // Create barangays for this city
            foreach ($barangayNames as $barangayName) {
                Barangay::create([
                    'city_id' => $city->id,
                    'name' => $barangayName,
                    'code' => $cityData['code'] . '-' . strtoupper(str_replace(' ', '_', $barangayName)),
                ]);
            }
        }
    }
} 