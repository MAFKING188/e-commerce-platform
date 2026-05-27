<nav>
    <a href="{{route('home')}}">Home</a>
    <a href="{{route(shop)}}">Shop</a>

    @@auth
        <a href="{{route('cart.index')}}">Cart</a>
        <a href="{{route('orders.index')}}">orders</a>
    @endauth

    @@guest
        <a href="{{route('login')}}">Login</a>
    @endguest
</nav>