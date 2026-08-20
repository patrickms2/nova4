@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">Tours</div>

                <div class="card-body">
                    <div class="row">
                        @foreach($tours as $tour)
                            <div class="col-md-4 mb-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title">{{ $tour['name'] }}</h5>
                                        <p class="card-text">{{ $tour['description'] }}</p>
                                        <p class="card-text"><strong>Price: ${{ $tour['price'] }}</strong></p>
                                        <a href="#" class="btn btn-primary">View Details</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
