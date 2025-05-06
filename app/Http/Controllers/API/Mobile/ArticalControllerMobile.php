<?php

namespace App\Http\Controllers\api\mobile;

use App\Http\Controllers\Controller;
use App\Models\ArticalModel;
use App\Models\CategoriesArticalModel;
use Illuminate\Http\Request;

class ArticalControllerMobile extends Controller
{

    public function index(Request $request)
    {
        $query = ArticalModel::orderBy('created_at', 'desc')->where('status', 1);
        if (!empty($request->category_artical_id)) {
            $query->where('category_artical_id', $request->category_artical_id);
        }
        if (!empty($request->is_hot)) {
            $query->where('is_hot', 1);
        }
        $artical = $query->paginate(50)->toArray();
        $artical['message'] = 'Thành công';
        $artical['status'] = 200;
        return response()->json($artical);
    }

    public function categories(Request $request)
    {
        $query = CategoriesArticalModel::orderBy('created_at', 'desc');
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }
        $articalCategories = $query->paginate(50)->toArray();
        $articalCategories['message'] = 'Thành công';
        $articalCategories['status'] = 200;
        return response()->json($articalCategories);
    }
}
