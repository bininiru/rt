<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ZipfCoreController extends Controller
{
    public function calculate(Request $request)
    {
        $text = $request->input('text');

        $normalized_text = mb_strtolower($text);

        $words = preg_split('/[\W]+/', $normalized_text);

        $words_stat = collect($words)->countBy()->sort();

        return response()->json([
            'words' => $words,
        ]);
    }
}
