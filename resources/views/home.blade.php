@extends('layouts.app')

@section('content')
    <route-builder :items="['foobar', 'bar', 'fizz', 'buzz']"></route-builder>
@endsection