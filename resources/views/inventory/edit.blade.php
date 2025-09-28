@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h2 mb-4">在庫を編集</h1>

            <form method="POST" action="{{ route('inventory.update', $inventory) }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="product_id" class="form-label">商品</label>
                    <select name="product_id" id="product_id" class="form-select">
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" {{ $inventory->product_id == $product->id ? 'selected' : '' }}>
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="store_id" class="form-label">店舗</label>
                    <select name="store_id" id="store_id" class="form-select">
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}" {{ $inventory->store_id == $store->id ? 'selected' : '' }}>
                                {{ $store->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="quantity" class="form-label">数量</label>
                    <input type="number" name="quantity" id="quantity" value="{{ $inventory->quantity }}" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">更新する</button>
                <a href="{{ route('inventory.index') }}" class="btn btn-secondary">キャンセル</a>
            </form>
        </div>
    </div>
</div>
@endsection