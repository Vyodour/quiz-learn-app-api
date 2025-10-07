<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Lessons;
use App\Models\QuizInformation; // Asumsi model ini sudah ada
use App\Models\CodeChallenge; // Asumsi model ini sudah ada

class ContentShowResource extends JsonResource
{
    /**
     * Transform the resource into an array (Detail View).
     * Resource ini dirancang untuk memproses unit-unit Polymorphic yang diurutkan (Lesson, Quiz, Challenge).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'module_id' => $this->module_id,
            'order_in_module' => $this->order, // Kolom 'order' dari tabel contents

            // EAGER LOADING: Memproses relasi orderedUnits (Polymorphic)
            // Relasi ini harus di-eager load: $content->load('orderedUnits.orderedUnit')
            'ordered_units' => $this->whenLoaded('orderedUnits', function () {

                // Gunakan map untuk mengiterasi setiap entri urutan
                return $this->orderedUnits->map(function ($unitOrder) {

                    $unit = $unitOrder->orderedUnit; // Objek Polymorphic (Lessons, QuizInformation, dll.)

                    $unitData = null;
                    $unitType = null;
                    $resourceClass = null;

                    // Menentukan tipe unit dan Resource yang akan digunakan
                    switch ($unitOrder->ordered_unit_type) {
                        case Lessons::class:
                            $unitType = 'lesson';
                            $resourceClass = LessonResource::class;
                            break;

                        case QuizInformation::class:
                            $unitType = 'quiz';
                            // Jika QuizResource belum ada, gunakan manual mapping sederhana
                            $unitData = [
                                'id' => $unit->id,
                                'name' => $unit->name,
                                'passing_score' => $unit->passing_score,
                            ];
                            break;

                        case CodeChallenge::class:
                            $unitType = 'challenge';
                            // Jika CodeChallengeResource belum ada, gunakan manual mapping sederhana
                            $unitData = [
                                'id' => $unit->id,
                                'title' => $unit->title,
                                'language' => $unit->language,
                            ];
                            break;

                        default:
                            $unitType = 'unknown';
                            $unitData = $unit->toArray();
                            break;
                    }

                    // Jika kelas resource (seperti LessonResource) ditentukan, gunakan itu
                    if ($resourceClass && !$unitData) {
                        $unitData = new $resourceClass($unit);
                    }

                    // Mengembalikan struktur seragam untuk front-end (Flutter)
                    return [
                        'order_id' => $unitOrder->id,
                        'order_number' => $unitOrder->order,
                        'unit_type' => $unitType, // lesson, quiz, challenge
                        'unit_data' => $unitData, // Resource / Data dari unit spesifik
                    ];
                });
            }),

            // Relasi Module: Dimuat hanya jika di-eager load (untuk navigasi)
            'module' => $this->whenLoaded('module', function () {
                // Menggunakan ModuleSummaryResource agar ringan
                return new ModuleSummaryResource($this->module);
            }),
        ];
    }
}
