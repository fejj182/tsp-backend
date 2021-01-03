@extends('layouts.app')

@section('content')
    <route-builder :stations="{{$stations}}"></route-builder>
@endsection