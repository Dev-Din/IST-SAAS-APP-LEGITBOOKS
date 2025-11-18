@extends('layouts.admin')

@section('title', 'Edit Admin')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Edit Admin User</h1>
        <div class="bg-white shadow rounded-lg p-6">
            <form method="POST" action="{{ route('admin.admins.update', $admin) }}">
                @method('PUT')
                @include('admin.admins.form', ['admin' => $admin])
            </form>
        </div>
    </div>
</div>
@endsection
