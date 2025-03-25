<?php

namespace App\Traits;

use App\Models\ProductsModel;
use App\Models\Rating;
use Illuminate\Support\Facades\Session;

trait Product
{

    public static function addSession($product_id)
    {
        // Lấy danh sách sản phẩm đã xem từ session (mặc định là mảng rỗng)
        $listProduct = session()->get('productViewed', []);

        // Kiểm tra sản phẩm đã tồn tại trong danh sách chưa
        $exists = collect($listProduct)->contains('id', $product_id);

        // Lấy dữ liệu sản phẩm từ database
        $productCurrent = ProductsModel::with('productImages2')->where('id', $product_id)->first();

        if (!$productCurrent) {
            return; // Nếu không tìm thấy sản phẩm, thoát hàm
        }


        // ============================ Lưu trữ đánh giá ============================


        $ratings = Rating::where('product_id', $productCurrent->id)
            ->where('status_id', 17)
            ->selectRaw('rating_value, COUNT(*) as count')
            ->groupBy('rating_value')
            ->get();

        // Chuyển dữ liệu về dạng mảng với các giá trị từ 1 đến 5
        $ratingsData = [
            5 => 0,
            4 => 0,
            3 => 0,
            2 => 0,
            1 => 0,
        ];

        $totalReviews = 0;

        foreach ($ratings as $rating) {
            $ratingsData[$rating->rating_value] = $rating->count;
            $totalReviews += $rating->count;
        }

        $average_rating =
            $totalReviews > 0
            ? round(
                array_sum(
                    array_map(
                        fn($key, $val) => $key * $val,
                        array_keys($ratingsData),
                        $ratingsData,
                    ),
                ) / $totalReviews,
                1,
            )
            : 0;

        $progressTotal = ($average_rating / 5) * 100;

        // ========================================================

        // Tạo một mảng chứa thông tin sản phẩm
        $productData = [
            'id'         => $productCurrent->id,
            'image1'     => $productCurrent->image,
            'image2'     => $productCurrent->productImages2->image ?? null,
            'name'       => $productCurrent->name,
            'price_new'  => $productCurrent->price,
            'price_old'  => $productCurrent->price_old,
            'slug'       => $productCurrent->slug,
            'image_alt'  => $productCurrent->image_alt,
            'total_rating'     => $totalReviews,
            'average_rating'   => $progressTotal,
        ];

        if (!$exists) {
            // Nếu chưa có, thêm vào đầu danh sách
            array_unshift($listProduct, $productData);
        } else {
            // Nếu đã có, cập nhật thông tin
            foreach ($listProduct as &$item) {
                if ($item['id'] == $product_id) {
                    $item = $productData;
                    break;
                }
            }
        }

        // Giới hạn danh sách sản phẩm đã xem (ví dụ: tối đa 10 sản phẩm)
        $listProduct = array_slice($listProduct, 0, 10);

        // Lưu vào session
        session()->put('productViewed', $listProduct);
    }
}
