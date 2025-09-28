<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Store;
use App\Models\PurchaseOrder; 

class InventoryController extends Controller
{
    //
    public function index(Request $request)
    {
        // Eloquentクエリビルダーを初期化し、基本的なリレーションを読み込む
        $query = Inventory::with(['product', 'store']);

        // --- フィルター機能 ---
        // 1. 商品名による絞り込み
        if ($request->filled('product_name')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->product_name . '%');
            });
        }
        // 2. 店舗による絞り込み
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        // --- ステータスによる絞り込み ---
        $statusFilter = $request->input('status');
        if ($statusFilter) {
            if ($statusFilter === 'pending') {
                // 「入荷待ち」の商品を検索
                $query->whereHas('pendingPurchaseOrder');
            } elseif ($statusFilter === 'reorder') {
                // 「要発注」の商品を検索 (発注点を下回り、かつ入荷待ちでない)
                $query->whereColumn('quantity', '<=', 'reorder_point')
                      ->whereDoesntHave('pendingPurchaseOrder');
            } elseif ($statusFilter === 'in_stock') {
                // 「在庫あり」の商品を検索 (発注点を上回っている)
                $query->whereColumn('quantity', '>', 'reorder_point');
            }
        }

        // ページネーションを適用して、メインの在庫データを取得
        $inventories = $query->latest()->paginate(25);
        
        // --- 発注済み情報の紐付け（最重要ロジック） ---
        // ページに表示されている在庫に対応する「入荷待ち」注文のみを取得
        if ($inventories->isNotEmpty()) {
            // 1. ページ上の在庫データから、検索キーのリストを作成
            $inventoryKeys = $inventories->map(function ($inventory) {
                return ['product_id' => $inventory->product_id, 'store_id' => $inventory->store_id];
            });

            // 2. 作成したキーリストを使い、関連する入荷待ち注文だけを一度のクエリで効率的に取得
            $pendingOrdersQuery = PurchaseOrder::where('status', '!=', 'completed');
            $pendingOrdersQuery->where(function ($query) use ($inventoryKeys) {
                foreach ($inventoryKeys as $key) {
                    $query->orWhere(function ($q) use ($key) {
                        $q->where('product_id', $key['product_id'])
                          ->where('store_id', $key['store_id']);
                    });
                }
            });
            $pendingOrders = $pendingOrdersQuery->get()->keyBy(fn($item) => $item->product_id . '-' . $item->store_id);

            // 3. 各在庫データに、対応する入荷待ち情報をプロパティとして動的に追加
            $inventories->each(function ($inventory) use ($pendingOrders) {
                $key = $inventory->product_id . '-' . $inventory->store_id;
                $inventory->pendingPurchaseOrder = $pendingOrders->get($key);
            });
        }
        
        // フィルター用の店舗リストを取得
        $stores = Store::all();

        // 全てのデータをビューに渡す
        return view('inventory.index', [
            'inventories' => $inventories,
            'stores' => $stores,
            'product_name' => $request->input('product_name'),
            'store_id' => $request->input('store_id'),
            'status' => $statusFilter,
        ]);
    }

    public function create()
    {
        // フォームの選択肢ように商品と店舗のデータを取得
        $products = Product::all();
        $stores = Store::all();

        // inventory.createビューにデータを渡す
        return view('inventory.create', [
            'products' => $products,
            'stores' => $stores,
        ]);
    }

    public function store(Request $request)
    {
        // バリデーション
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'store_id' => 'required|exists:stores,id',
            'quantity' => 'required|integer|min:0',
        ]);

        /***********************************************************************************************
        
         * 1つ目の配列（product_idとstore_id）に一致するデータがinventoriesテーブルに存在すれば、
         * 2つ目の配列の内容（quantity）で**更新(Update)**します。
            一致するデータが存在しなければ、1つ目と2つ目の配列を合体させて新しいレコードを**作成(Create)**します。

         *************************************************************************************************/
        $inventory = Inventory::updateOrCreate(
            [
                'product_id' => $validated['product_id'],
                'store_id' => $validated['store_id'],
            ],
            [
                'quantity' => $validated['quantity'],
            ]
        );
        // 在庫一覧ページへリダイレクト
        return redirect()->route('inventory.index')->with('success', '在庫情報を保存しました。');
    }

    public function edit(Inventory $inventory)
    {
        // フォームの選択肢ように商品と店舗のデータを取得
        $products = Product::all();
        $stores = Store::all();

        // inventory.editビューにデータを渡す
        return view('inventory.edit', [
            'inventory' => $inventory,
            'products' => $products,
            'stores' => $stores,
        ]);
    }

    public function update(Request $request, Inventory $inventory)
    {
        // バリデーション
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'store_id' => 'required|exists:stores,id',
            'quantity' => 'required|integer|min:0',
        ]);

        // 在庫情報を更新
        $inventory->update($validated);

        // 在庫一覧ページへリダイレクト
        return redirect()->route('inventory.index')->with('success', '在庫情報を更新しました。');
    }

    public function destroy(Inventory $inventory)
    {
        // データを削除
        $inventory->delete();

        // 在庫一覧ページへリダイレクト
        return redirect()->route('inventory.index')->with('success', '在庫情報を削除しました。');
    }

}
