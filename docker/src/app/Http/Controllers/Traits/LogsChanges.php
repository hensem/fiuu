<?php

namespace App\Http\Controllers\Traits;

use App\Models\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

trait LogsChanges
{
    protected function logChange(string $controller, string $method, string $table, string $column, $old, $new)
    {
        Log::create([
            'controller' => $controller,
            'method'     => $method,
            'table'      => $table,
            'column'     => $column,
            'old_value'  => is_null($old) ? null : (string)$old,
            'new_value'  => is_null($new) ? null : (string)$new,
            'user'       => Auth::id(),
            'date_time'  => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }

    protected function logModelDiff(string $controller, string $method, $before, $after, array $only = [])
    {
        $table = $after->getTable();
        $oldArr = $before ? $before->getAttributes() : [];
        $newArr = $after->getAttributes();

        $keys = $only ?: array_unique(array_merge(array_keys($oldArr), array_keys($newArr)));

        foreach ($keys as $key) {
            $old = $oldArr[$key] ?? null;
            $new = $newArr[$key] ?? null;
            if ($old !== $new) {
                $this->logChange($controller, $method, $table, $key, $old, $new);
            }
        }
    }
}
