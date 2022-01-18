<?php

namespace App\Http\Controllers;

use App\Models\LinkImportHistory;
use App\Models\Meta\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use RAY\Amazon\Exception\ProductLinkNotFoundException;
use RAY\HtmlDownloader;
use RAY\Yahoo\Exception\JanCodeRequiredException;
use RAY\Yahoo\ItemImporter;
use Symfony\Component\DomCrawler\Crawler;

class YahooProductsController extends Controller
{
    public function importByProductLink(Request $request)
    {
        /**
         * @var User $user
         */
        $user = User::query()
            ->where('role', UserRole::ADMIN)
            ->whereNotNull('yahoo_app_id')
            ->first();
        if ($user == null) {
            $this->error('Could not find an admin user.');
            return 0;
        }

        if ($request->isMethod('POST')) {
            $validator = Validator::make(
                $request->all(),
                [
                    'product_link' => 'required|url',
                ],
                [
                    'product_link.required' => __('Enter a valid Yahoo! Shopping product link.'),
                    'product_link.url' => __('Enter a valid Yahoo! Shopping product link.')
                ]
            );

            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator);
            }
            $validated = $validator->validated();
            $html = HtmlDownloader::download($validated['product_link']);
            $crawler = new Crawler($html);
            $link = $crawler->filter('meta[property="twitter:image"]')->attr('content');
            $link = explode('/', $link);
            $itemCode = end($link);
            $itemImporter = new ItemImporter(Auth::user()->yahoo_app_id);
            $itemImporter->janCodeRequired();
            $itemImporter->amazonProductRequired();
            try {
                $itemImporter->importItemCode($itemCode);
            } catch (JanCodeRequiredException $exception) {
                return redirect()->back()->with('error', __('This product does not have a JAN code.'));
            } catch (ProductLinkNotFoundException $exception) {
                return redirect()->back()->with('error', __('Could not find Amazon product by JAN code.'));
            } catch (\Exception $exception) {
                return redirect()->back()->with('error', __('Product could not import. Please contact us.'));
            }
            return redirect()->back()->with('success', __('Product has been imported.'));
        }

        return view('import.product-link');
    }

    public function importBySearchLink(Request $request)
    {
        /**
         * @var User $user
         */
        $user = User::query()
            ->where('role', UserRole::ADMIN)
            ->whereNotNull('yahoo_app_id')
            ->first();
        if ($user == null) {
            $this->error('Could not find an admin user.');
            return 0;
        }

        if ($request->isMethod('POST')) {
            $validator = Validator::make(
                $request->all(),
                [
                    'search_link' => 'required|url',
                    'num_of_search_results' => 'required|numeric',
                ],
                [
                    'search_link.required' => __('Enter a valid Yahoo! Shopping search link.'),
                    'search_link.url' => __('Enter a valid Yahoo! Shopping search link.'),
                    'num_of_search_results.required' => __('Enter a valid search results count.'),
                    'num_of_search_results.numeric' => __('Enter a valid search results count.'),
                ]
            );

            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator);
            }
            $validated = $validator->validated();
            $history = new LinkImportHistory();
            $history->user_id = Auth::id();
             $history->type = 'SearchLink';
            $history->configs = [
                'link' => $validated['search_link'],
                'num_of_search_results' => $validated['num_of_search_results']
            ];
            $history->created_at = new \DateTime();
            $history->save();
            return redirect()->back()->with('success', __('Link has been added in the queue. It will be imported soon.'));
        }

        return view('import.search-link');
    }

    public function importByKeyword(Request $request)
    {
         /**
         * @var User $user
         */
        $user = User::query()
            ->where('role', UserRole::ADMIN)
            ->whereNotNull('yahoo_app_id')
            ->first();
        if ($user == null) {
            $this->error('Could not find an admin user.');
            return 0;
        }

        if ($request->isMethod('POST')) {
            $validator = Validator::make(
                $request->all(),
                [
                    'keyword' => 'required',
                    'num_of_results' => 'required|numeric'
                ],
                [
                    'keyword.required' => __('Enter a valid keyword.'),
                    'num_of_results.required' => __('Enter a valid number of expected results.'),
                    'num_of_results.numeric' => __('Enter a valid number only.'),
                ]
            );

            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator);
            }
            $validated = $validator->validated();
            $history = new LinkImportHistory();
            $history->user_id = Auth::id();
            $history->type = 'Keyword';
            $history->configs = [
                'keyword' => $validated['keyword'],
                'num_of_results' => $validated['num_of_results']
            ];
            $history->created_at = new \DateTime();
            $history->save();
            return redirect()->back()->with('success', __('Keyword has been added in the queue. It will be imported soon.'));
        }

        return view('import.keyword');
    }
}
