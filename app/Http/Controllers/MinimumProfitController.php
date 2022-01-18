<?php

namespace App\Http\Controllers;

use App\Models\MinimumProfit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MinimumProfitController extends Controller
{
    public function index(Request $request)
    {
        $pagination = MinimumProfit::query()
            ->where('user_id', Auth::id())
            ->orderBy('min_price')
            ->orderBy('max_price')
            ->paginate(30);
        return view('minimum_profit.search', [
            'pagination' => $pagination
        ]);
    }

    public function save(Request $request)
    {
        /**
         * @var MinimumProfit $minimumProfit
         */
        $minimumProfit = MinimumProfit::query()
            ->where('user_id', Auth::id())
            ->where('id', $request->get('id'))
            ->firstOrNew();
        if ($request->isMethod('POST')) {
            $validator = Validator::make(
                $request->all(),
                [
                    'min_price' => 'required',
                    'max_price' => 'required',
                    'profit' => 'required'
                ],
                [
                    'min_price.required' => __(trans('Minimum price is required.')),
                    'max_price.required' => __(trans('Maximum price is required.')),
                    'profit.required' => __(trans('Profit is required.'))
                ]
            );
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator);
            }
            $validated = $validator->validated();
            $isMinPriceExists = MinimumProfit::query()
                ->where('user_id', Auth::id())
                ->where('id', '!=', $minimumProfit->id)
                ->whereRaw('? BETWEEN min_price AND max_price', [$validated['min_price']])
                ->count();
            if ($isMinPriceExists) {
                 return redirect()->back()->withInput()->with('error', __(trans('Minimum price is already added.')));
            }
            $isMaxPriceExists = MinimumProfit::query()
                ->where('user_id', Auth::id())
                ->where('id', '!=', $minimumProfit->id)
                ->whereRaw('? BETWEEN min_price AND max_price', [$validated['max_price']])
                ->exists();
            if ($isMaxPriceExists) {
                return redirect()->back()->withInput()->with('error', __(trans('Maximum price is already added.')));
            }
            $isPriceRangeExists = MinimumProfit::query()
                ->where('user_id', Auth::id())
                ->where('id', '!=', $minimumProfit->id)
                ->whereBetween('min_price', [$validated['min_price'], $validated['max_price']])
                ->whereBetween('max_price', [$validated['min_price'], $validated['max_price']])
                ->exists();
            if ($isPriceRangeExists) {
                return redirect()->back()->withInput()->with('error', __(trans('Price range is already added.')));
            }
            $minimumProfit->user_id = Auth::id();
            $minimumProfit->min_price = $validated['min_price'];
            $minimumProfit->max_price = $validated['max_price'];
            $minimumProfit->profit = $validated['profit'];
            $minimumProfit->save();
            if ($request->get('id') > 0) {
                return redirect()->back()->with('success', __(trans('Minimum profit has been updated!')));
            } else {
                return redirect()->back()->with('success', __(trans('Minimum profit has been created!')));
            }
        }
        return view('minimum_profit.save', [
            'minimum_profit' => $minimumProfit
        ]);
    }

    public function delete(Request $request)
    {
        $id = $request->get('id');
        MinimumProfit::query()->where('user_id', Auth::id())->where('id', $id)->delete();
        return redirect()->route('minimum_profits.search')->with('success', __(trans('Minimum profit has been deleted!')));
    }
}
