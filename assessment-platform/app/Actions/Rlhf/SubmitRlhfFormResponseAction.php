<?php

namespace App\Actions\Rlhf;

use App\Enums\RlhfFormStage;
use App\Models\AttemptRlhfTurn;
use Illuminate\Support\Facades\DB;

final class SubmitRlhfFormResponseAction
{
    /**
     * @param  array<string, mixed>  $responses
     */
    public function handle(AttemptRlhfTurn $turn, RlhfFormStage $stage, array $responses): AttemptRlhfTurn
    {
        DB::transaction(function () use ($turn, $stage, $responses): void {
            if ($responses === []) {
                return;
            }

            foreach ($responses as $fieldKey => $value) {
                $turn->formResponses()->updateOrCreate(
                    [
                        'rlhf_turn_id' => $turn->id,
                        'stage' => $stage,
                        'field_key' => $fieldKey,
                    ],
                    [
                        'value' => is_array($value)
                            ? json_encode(array_values($value), JSON_THROW_ON_ERROR)
                            : (string) $value,
                    ]
                );
            }
        });

        return $turn->fresh(['formResponses']);
    }
}
