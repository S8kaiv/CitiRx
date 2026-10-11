<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pearl\IndexMicroPearlRequest;
use App\Models\MicroPearl;
use App\Services\MicroPearlLibraryService;
use Illuminate\View\View;

class MicroPearlController extends Controller
{
    public function __construct(
        private readonly MicroPearlLibraryService $library,
    ) {}

    public function index(
        IndexMicroPearlRequest $request
    ): View {
        return view(
            'pearls.index',
            $this->library->browse(
                $request->validated()
            )
        );
    }

    public function show(
        MicroPearl $pearl
    ): View {
        abort_unless(
            $pearl->isPublished(),
            404
        );

        $pearl->loadMissing([
            'competency.domain',
        ]);

        return view(
            'pearls.show',
            [
                'pearl' => $pearl,
            ]
        );
    }
}
