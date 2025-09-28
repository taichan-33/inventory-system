@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h2 mb-4">新規在庫を登録</h1>
            
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('inventory.store') }}">
                @csrf
                <div class="mb-3">
                    <label for="product_id" class="form-label">商品</label>
                    <select name="product_id" id="product_id" class="form-select">
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="store_id" class="form-label">店舗</label>
                    <select name="store_id" id="store_id" class="form-select">
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}">{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="quantity" class="form-label">数量</label>
                    <input type="number" name="quantity" id="quantity" value="0" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">登録する</button>
                <a href="{{ route('inventory.index') }}" class="btn btn-secondary">キャンセル</a>
            </form>
        </div>
    </div>
</div>
@endsection