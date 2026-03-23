<?php

namespace App\Http\Controllers;

use App\Diagnostics\DiagnosticRunner;
use Illuminate\View\View;

class DiagnoseController
{
    public function __invoke(DiagnosticRunner $runner): View
    {
        $results = $runner->run();
        $summary = $runner->summary($results);
        $variant = config('deploy-test');

        return view('diagnose', compact('results', 'summary', 'variant'));
    }
}
