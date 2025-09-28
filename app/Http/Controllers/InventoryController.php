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
        // クエリビルダーを初期化
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

        // データを25件ごとにページネーション
        $inventories = $query->paginate(25);
        
        // フィルター用の店舗リストを取得
        $stores = Store::all();

        // 1. 発注済み（未完了）の注文を取得
        $pendingOrders = PurchaseOrder::where('status', '!=', 'completed')
            ->get()
            ->keyBy(fn ($item) => $item->product_id . '-' . $item->store_id);

        // 2. ページネーションされた在庫データに、発注済み情報を直接紐付ける
        $inventories->getCollection()->transform(function ($inventory) use ($pendingOrders) {
            $key = $inventory->product_id . '-' . $inventory->store_id;
            // 'pendingOrder' という名前で、発注済み情報をinventoryオブジェクトに追加
            $inventory->pendingOrder = $pendingOrders->get($key);
            return $inventory;
        });

        // 絞り込み条件をビューに渡して、入力値を保持する
        return view('inventory.index', [
            'inventories' => $inventories,
            'stores' => $stores,
            'product_name' => $request->product_name,
            'store_id' => $request->store_id,
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
