@extends('layouts.app')

@section('content')
    <h1>Dashboard</h1>

    <ul>
        <li><a href="{{route('products.index')}}">Manage Products</a></li>
        <li><a href="{{route('orders.index')}}">View Orders</a></li>
        <li><a href="{{route('users.index')}}">Manage Users</a></li>
    </ul>
@endsection