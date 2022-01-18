<?php

namespace App\Http\Controllers;

use App\Models\ExportProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExportedProductsController extends Controller
{
    public function index(Request $request)
    {
       $itemType = $request->get('item_type', 'draft');
        $products = ExportProduct::query()->where('user_id', Auth::id())->orderBy('name');
        if ($itemType === 'draft') {
            $products = $products
                ->where('is_published', 0)
                ->where('is_exported', 0);
        } elseif ($itemType === 'published') {
            $products
                ->where('is_published', 1)
                ->where('is_exported', 0);
        } else {
            $products
                ->where('is_published', 1)
                ->where('is_exported', 1);
        }
        return view('export_product.search', [
            'products' => $products->get(),
            'item_type' => $itemType
        ]);
    }

    public function export()
    {
        $affectedRows = ExportProduct::query()
            ->where('user_id', Auth::id())
            ->where('is_published', 1)
            ->where('is_exported', 0)
            ->update([
                'is_exported' => 1
            ]);
        return redirect()
            ->route('exported_products.search', ['item_type' => 'exported'])
            ->with('success', __(trans(':count product has been exported.', ['count' => $affectedRows])));
           
    }

    public function delete(Request $request)
    {
        ExportProduct::query()
            ->where('user_id', Auth::id())
            ->where('is_exported', 0)
            ->where('id', $request->get('id'))
            ->delete();
        return redirect()->back()->with('success', __(trans('Product has been deleted.')));
    }

   
}
