<?php

namespace App\Http\Controllers\MyProduct;

use App\Http\Controllers\Controller;
use App\Models\ExportProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use RAY\Amazon\ProductPageScraper;

class AutoStockSettingController extends Controller
{
    public function index(Request $request)
    {
        /**
         * @var ExportProduct $exportedProduct
         */
        $exportedProduct = ExportProduct::query()
            ->where('user_id', Auth::id())
            ->where('id', $request->get('id'))
            ->first();
        if ($exportedProduct == null) {
            return redirect()->route('my_products.search');
        }
        if ($request->isMethod('POST')) {
            $validator = Validator::make(
                $request->all(),
                [
                    'is_auto_stock_enabled' => 'required',
                    'stock_service' => 'nullable',
                    'stock_product_code' => 'nullable'
                ]
            );
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator);
            }
            $validated = $validator->validated();
            try {
                $amazonProductResult = ProductPageScraper::scrapeByAsin($validated['stock_product_code']);
            } catch (\Throwable $throwable) {
                Log::error($throwable);
                return redirect()->back()->withInput()->with('error', __(trans('We could not fetch Amazon product information. Please try again or contact site administrator.')));
            }
            $exportedProduct->is_auto_stock_enabled = $validated['is_auto_stock_enabled'];
            $exportedProduct->stock_service = $validated['stock_service'];
            $exportedProduct->stock_product_code = $validated['stock_product_code'];
            $exportedProduct->stock_price = intval($amazonProductResult->price);
            $exportedProduct->stock_shipping_fee = intval($amazonProductResult->shippingFee);
            $exportedProduct->save();
            return redirect()->back()->withInput()->with('success', __(trans('Setting has been saved.')));
        }
        return view('my_products.auto_stock_setting', [
            'exported_product' => $exportedProduct
        ]);
    }
}
