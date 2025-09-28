@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">在庫一覧</h1>
        <a href="{{ route('inventory.create') }}" class="btn btn-primary">＋ 新規在庫を登録する</a>
    </div>

    {{-- フィルター検索フォーム --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('inventory.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="product_name" class="form-label">商品名で検索</label>
                        <input type="text" name="product_name" id="product_name" class="form-control" value="{{ $product_name ?? '' }}" placeholder="商品名の一部を入力">
                    </div>
                    <div class="col-md-5">
                        <label for="store_id" class="form-label">店舗で絞り込み</label>
                        <select name="store_id" id="store_id" class="form-select">
                            <option value="">すべての店舗</option>
                            @foreach ($stores as $store)
                                <option value="{{ $store->id }}" {{ (isset($store_id) && $store_id == $store->id) ? 'selected' : '' }}>
                                    {{ $store->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> 検索
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th scope="col">商品名</th>
                        <th scope="col">店舗名</th>
                        <th scope="col">在庫数</th>
                        <th scope="col">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inventories as $inventory)
                        <tr>
                            <td>{{ $inventory->product->name }}</td>
                            <td>{{ $inventory->store->name }}</td>
                            <td>{{ $inventory->quantity }} 個</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="{{ route('inventory.edit', $inventory) }}" class="btn btn-sm btn-outline-secondary me-2">編集</a>
                                    <button type="button" class="btn btn-sm btn-outline-danger delete-btn" data-action="{{ route('inventory.destroy', $inventory) }}">削除</button>
                                    <form method="POST" action="{{ route('sales.store') }}" class="ms-3 border-start ps-3">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $inventory->product_id }}">
                                        <input type="hidden" name="store_id" value="{{ $inventory->store_id }}">
                                        <div class="input-group input-group-sm">
                                            <input type="number" name="quantity_sold" value="1" min="1" class="form-control" style="width: 60px;">
                                            <button type="submit" class="btn btn-success">売る</button>
                                        </div>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted p-5">
                                <p>該当する在庫データはありません。</p>
                                <a href="{{ route('inventory.index') }}" class="btn btn-sm btn-outline-secondary">検索条件をクリア</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- ページネーションリンク --}}
            <div class="d-flex justify-content-center">
                {{ $inventories->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">削除の確認</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>本当にこの在庫情報を削除しますか？この操作は取り消せません。</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <form id="deleteForm" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">削除する</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    const deleteForm = document.getElementById('deleteForm');

    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function() {
            const actionUrl = this.dataset.action;
            deleteForm.action = actionUrl;
            deleteModal.show();
        });
    });
</script>
@endpush