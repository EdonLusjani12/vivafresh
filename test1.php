@extends('layouts.master')
@section('title', "Супермаркет со одлична понуда и супер цени") 
@section('content')


 
<div class="header">

</div>

<div class="header_img">
  <img class="img-fluid mt-3" src="{{asset("./admin/img/logo_group.png")}}" alt="" srcset="">
</div>

<div class="p-0">
  <div class="container-fluid p-0">
    <img src="{{asset("./admin/img/kitgo-banner.jpg")}}" class="img-fluid w-100 myu" alt="Jumbotron Image">
  </div>
</div>
<div class="container">
  {{-- <h2>Изберете маркет за да ги дознаете попустите</h2> --}}
</div>
<div class="container heighttm">
  <div class="d-flex flex-wrap">
      @foreach ($cities as $item)
          <div class="p-2 col-6 col-sm-4 col-md-3 col-lg-2  ">
 

              
          <div class="dropdown w-100">
            <button class="btn btn-cb dropdown-toggle btn-block" title="Кликни за да дознаеш сите понуди" type="button" data-bs-toggle="dropdown" aria-expanded="false" onclick="getPricelist ({{ $item->id }})" id="{{ $item->id }}">
              {{ $item->city_name }}
            </button>
            <ul class="dropdown-menu" id="menu_{{ $item->id }}">
              
            </ul>
          </div>

          </div>





      @endforeach
  </div>
</div>

<footer class="footer"></footer>
@endsection
