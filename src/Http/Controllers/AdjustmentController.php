<?php

namespace Patrixsmart\Adjustfly\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Patrixsmart\Adjustfly\Models\Adjustment;

class AdjustmentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Adjustment::paginate(50);
    }

    /**
     * Display the specified resource.
     *
     * @param  \Patrixsmart\Adjustfly\Models\Adjustment  $adjustment
     * @return \Illuminate\Http\Response
     */
    public function show(Adjustment $adjustment)
    {
        return $adjustment;
    }
}
