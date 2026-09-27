<?php

namespace App\Http\Controllers;

use App\Models\Information;
use Illuminate\Http\Request;

class InformationController extends Controller
{
    public function menu($index){
      // return session('set_userdata') ? 'ada' : "tidak ada";
      // return "<h1>Hello</h1>";
      return response()->json(Information::getMenuAreaAll());
    }
    
    public function menu2(){
      // return "<h1>Hello</h1>";
      return response()->json(Information::viewMenu2());
    }
    
    public function session(){
      return response()->json(Information::getSession());
    }
}
