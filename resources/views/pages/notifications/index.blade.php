@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9">

            <div class="row align-items-center mb-3">
                <div class="col">
                    <h2 class="h5 page-title">Notifikasi</h2>
                </div>
                @if (auth()->user()->unreadNotifications->count())
                    <div class="col-auto">
                        <form action="{{ route('notifications.read-all') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-light">
                                <span class="fe fe-check fe-16 mr-1"></span>Tandai semua dibaca
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card shadow">
                <div class="list-group list-group-flush">
                    @forelse ($notifications as $n)
                        @php
                            $data = $n->data;
                            $icon = match ($data['type'] ?? '') {
                                'min_stock' => 'fe-alert-triangle text-warning',
                                'near_expiry' => 'fe-clock text-danger',
                                'transfer_created' => 'fe-inbox text-primary',
                                'transfer_approved' => 'fe-check-circle text-success',
                                'transfer_shipped' => 'fe-truck text-info',
                                'transfer_received' => 'fe-package text-success',
                                'transfer_rejected' => 'fe-x-circle text-danger',
                                default => 'fe-info text-primary',
                            };
                        @endphp
                        <a href="{{ route('notifications.read', $n->id) }}"
                            class="list-group-item list-group-item-action {{ $n->read_at ? '' : 'bg-light' }}">
                            <div class="d-flex">
                                <span class="fe {{ $icon }} fe-16 mr-3 mt-1"></span>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between">
                                        <strong class="small">{{ $data['title'] ?? 'Notifikasi' }}</strong>
                                        <small class="text-muted">{{ $n->created_at->diffForHumans() }}</small>
                                    </div>
                                    <p class="mb-0 small text-muted">{{ $data['message'] ?? '' }}</p>
                                </div>
                                @unless ($n->read_at)
                                    <span class="badge badge-primary align-self-center ml-2">Baru</span>
                                @endunless
                            </div>
                        </a>
                    @empty
                        <div class="list-group-item text-center text-muted py-4">
                            Belum ada notifikasi.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="mt-3">
                {{ $notifications->links() }}
            </div>

        </div>
    </div>
@endsection
