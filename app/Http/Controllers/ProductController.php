<?php

namespace App\Http\Controllers;

use App\Product;
use App\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $products = Product::where('user_id', Auth::user()->id)->get();
        return view('users.merchants.products.index')->with('products', $products);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function createUlos()
    {
        return view('users.merchants.products.create_ulos');
    }

    public function createFood()
    {           
        return view('users.merchants.products.create_food');
    }

    public function createClothes()
    {        
        return view('users.merchants.products.create_clothes');
    }

    public function createAccessories()
    {        
        return view('users.merchants.products.create_accessories');
    }

    public function createMedicine()
    {        
        return view('users.merchants.products.create_medicine');
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $id)
    {
        $uploadedImages = $request->file('images');
        $imageNames = [];

        foreach ($uploadedImages as $image) {
            $imageName = time() . $image->getClientOriginalName();

            array_push($imageNames, $imageName);

            $destinationPath = public_path('/images');
            $image->move($destinationPath, $imageName);
        }
        
        
        $address = Profile::all()->where('user_id', Auth::user()->id)->first();
        $data = json_decode($address->address);
        $real_address = json_decode($data[0]);
        $address_merchant = $real_address->city_name;                

        $product = new Product();
        $product->user_id = Auth::user()->id;   
        $product->color = $request->color;

        if($id == 1) {            
            $product->cat_product = "ulos";
            $product->specification = json_encode([
                'dimention' => $request->dimention,
                'weight' => $request->weight
            ]);
        } else if($id == 2) {
            $product->cat_product = "pakaian";                        
            $product->specification = json_encode([
                'size' => $request->dimention,
                'weight' => $request->weight
            ]);
        } else if($id == 3) {            
            $product->cat_product = "makanan";
            $product->specification = json_encode([
                'size_pack' => $request->dimention,
                'weight' => $request->weight,
                'umur_simpan' => $request->color
            ]);
            $product->color = "-";

        } else if($id == 4) {
            $product->cat_product = "aksesoris";            
            $product->specification = json_encode([
                'size' => $request->dimention,
                'weight' => $request->weight
            ]);
        } else if($id == 5) {
            $product->cat_product = "obat";            
            $product->specification = json_encode([
                'jenis' => $request->dimention,
                'weight' => $request->weight
            ]);
            
            if($request->dimention == "Padat")
                $product->color = "-";

            else if($request->dimention == "Cair")
                $product->color = $request->color;

        }        
        
        $product->name = $request->name;
        $product->price = $request->price;
        $product->stock = $request->stock;
        $product->sold = 0;
        $product->category = $request->category;
        $product->description = $request->description;
        
        $product->images = json_encode($imageNames);
        $product->asal = $address_merchant;

        $product->save();

        return redirect('/merchant')->with('success', 'Product created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $product = Product::with(['reviews.customer.profile'])->where('id',$id)->first();

        // var_dump($product['cat_product']);
        return view('users.merchants.products.show')->with('product', $product);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $product = Product::find($id);
        return view('users.merchants.products.edit')->with('product', $product);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $newImageNames = [];

        if ($request->file('images')) {
            $uploadedImages = $request->file('images');

            foreach ($uploadedImages as $image) {
                $imageName = time() . $image->getClientOriginalName();

                array_push($newImageNames, $imageName);

                $destinationPath = public_path('/images');
                $image->move($destinationPath, $imageName);
            }
        }

        $product = Product::find($id);
        $product->name = $request->name;
        $product->price = $request->price;
        $product->description = $request->description;
        $product->category = $request->category;
        
        // echo $request->dimention. " " . $request->color;

        if($product->cat_product == "ulos") {
            $product->specification = json_encode([
                'dimention' => $request->dimention,
                'weight' => $request->weight
            ]);
            $product->color = $request->color;
        } else if($product->cat_product == "pakaian") {
            $product->specification = json_encode([
                'size' => $request->dimention,
                'weight' => $request->weight
            ]);

            $product->color = $request->color;
        } else if($product->cat_product == "makanan") {
            $product->specification = json_encode([
                'size_pack' => $request->dimention,
                'weight' => $request->weight,
                'umur_simpan' => $request->color
            ]);
        } else if($product->cat_product == "aksesoris") {
            $product->specification = json_encode([
                'size' => $request->dimention,
                'weight' => $request->weight
            ]);
            $product->color = $request->color;
        } else if($product->cat_product == "obat") {
            $product->specification = json_encode([
                'jenis' => $request->dimention,
                'weight' => $request->weight
            ]);

            if($request->dimention == "Padat") {
                $product->color = "-";
            }
            else if($request->dimention == "Cair") {
                $product->color = $request->color;
            }
        } 
        
        
        $imageNames = json_decode($product->images);
        $deletedImages = explode(",", $request->deletedImages);

        foreach ($deletedImages as $image) {
            if (false !== $key = array_search($image, $imageNames)) {
                unset($imageNames[$key]);
            }
        }

        $finalImageNames = array_merge($newImageNames, $imageNames);

        $product->images = json_encode($finalImageNames);
        $product->update();

        return redirect('products/' . $id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $product = Product::find($id)->delete();
        return redirect('/merchant');
        // return redirect('/products');
    }

    public function searchProduct(Request $request) {
        return view('users.homes.search');
    }
}
