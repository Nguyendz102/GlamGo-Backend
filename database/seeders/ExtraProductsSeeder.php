<?php

namespace Database\Seeders;

use App\Models\ProductImagesModel;
use App\Models\ProductsModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ExtraProductsSeeder extends Seeder
{
    public function run(): void
    {
        $images = [
            '1X8Iq2SEdUBAhteUvkoJSSFppeQgok7J2s8BHOGy.webp',
            '506DvJDZ3s1f6bvdlDaFbw9bcFhv2fmdsCcwCD6Q.webp',
            '96gyaTcIXcBQppdhhrsJmjQAjfIGukKl5WtFgCNH.jpg',
            'bP87d1Ro7xsvXcW8om3kOFrIHrBKukp7gWLstKlF.webp',
            'DKMYmP3QvTdrw6ricdt3CoR325lTK0gkAqBdl58y.webp',
            'ECQAByLk0L0EJve9ucMDmFoPUVAWseuo9Hd7I64B.jpg',
            'eWN4gCpnnhusNZ1FXVZK4515lGQgP9XAG6RdsKG3.webp',
            'FomrA1es9k67cKcQYPea3nvvKtrrwpXJiQvuPhuE.webp',
            'i1KBc31WNKsxq6RxygcbexT8bJ4klGfD75htB5QR.webp',
            'j0z33kvFVhiFwkBTpbL7cMxsCuSXAuMtYmjUF8D7.webp',
            'lY3tDUECBnA1u4kMDXfPM9KhOr8US4D2Zl3p86el.webp',
            'p5gHMJzW7DyLr0oiTZ1jH2VCyVmVycQK1VCo2vWS.webp',
            'pMXbcAqnINAswh5vqLmK06buha1HMfbP0dtWTH98.webp',
            'TXUQXczmDRbXQfhENvso0q57xVyfLM9Mfizqew7o.jpg',
            'VsVKJeRm4OJ3iluqy64jb7veZCIyKfED8sky2eOJ.webp',
            'WLPCwzicE9bp5kkXPDEW7Eg5zuUJK0WziZfaS15u.webp',
        ];

        $products = [
            [1, 'Giày Jordan Retro High phối màu trắng đỏ', 1290000, 1490000, '#jordan #sneaker #giaynam #streetwear'],
            [1, 'Giày Jordan Low cổ thấp da mềm đi phố', 1090000, 1290000, '#jordanlow #sneaker #giaythethao'],
            [1, 'Giày sneaker chunky đế cao phong cách Hàn Quốc', 890000, 1050000, '#sneaker #chunky #giaynu'],
            [1, 'Giày thể thao basic trắng unisex dễ phối đồ', 690000, 820000, '#giaytrang #sneaker #unisex'],
            [2, 'Quần jean nam ống suông xanh bạc wash nhẹ', 420000, 520000, '#quanjean #jeanongsuong #denim'],
            [2, 'Quần jean nữ lưng cao form rộng cá tính', 450000, 560000, '#jeannu #denim #streetstyle'],
            [2, 'Quần jean đen slim fit co giãn thoải mái', 390000, 490000, '#jeanden #slimfit #thoitrangnam'],
            [2, 'Quần jean rách gối phong cách đường phố', 470000, 590000, '#jeanrach #streetwear #denim'],
            [3, 'Áo sơ mi trắng Oxford dài tay công sở', 360000, 450000, '#aosomi #oxford #congso'],
            [3, 'Áo sơ mi kẻ caro form rộng unisex', 330000, 420000, '#somikercaro #oversize #unisex'],
            [3, 'Áo sơ mi linen ngắn tay mùa hè thoáng mát', 310000, 390000, '#linen #somimuahè #thoangmat'],
            [3, 'Áo sơ mi denim xanh bụi bặm trẻ trung', 390000, 490000, '#somidenim #denimshirt #casual'],
            [4, 'Quần âu nam slim fit màu đen công sở', 430000, 540000, '#quanau #congso #slimfit'],
            [4, 'Quần tây nữ ống đứng lưng cao thanh lịch', 460000, 580000, '#quantaynu #quanau #congso'],
            [4, 'Quần âu xám basic co giãn nhẹ', 410000, 510000, '#quanauxam #basic #thoitrangnam'],
            [4, 'Quần tây ống rộng phong cách Hàn Quốc', 480000, 600000, '#ongrong #koreanstyle #quanau'],
            [5, 'Áo polo nam cotton pique cổ bẻ lịch sự', 280000, 350000, '#polo #cotton #aopolonam'],
            [5, 'Áo polo nữ phối viền trẻ trung', 260000, 330000, '#polonu #thoitrangnu #basic'],
            [5, 'Áo polo unisex oversize thêu logo nhỏ', 300000, 380000, '#polounisex #oversize #localbrand'],
            [5, 'Áo polo thể thao thấm hút nhanh', 320000, 400000, '#polothethao #thoangmat #activewear'],
            [6, 'Áo hoodie nỉ bông trơn màu be form rộng', 420000, 520000, '#hoodie #oversize #nibong'],
            [6, 'Áo hoodie zip kéo khóa basic unisex', 450000, 560000, '#hoodiezip #unisex #basic'],
            [6, 'Áo hoodie in chữ minimal phong cách streetwear', 390000, 490000, '#hoodie #streetwear #minimal'],
            [6, 'Áo hoodie phối màu xanh navy cá tính', 430000, 540000, '#hoodienam #navy #thoitrangtre'],
            [7, 'Áo khoác bomber kaki chống gió nhẹ', 590000, 720000, '#bomber #aokhoac #kaki'],
            [7, 'Áo khoác denim xanh form rộng unisex', 620000, 760000, '#aokhoacdenim #denimjacket #unisex'],
            [7, 'Áo khoác dù hai lớp có mũ tiện dụng', 550000, 690000, '#aokhoacdu #chonggio #outdoor'],
            [7, 'Áo khoác cardigan dệt kim mềm mại', 490000, 620000, '#cardigan #aokhoaclen #koreanstyle'],
            [8, 'Túi tote canvas in chữ phong cách tối giản', 180000, 240000, '#tuitote #canvas #minimal'],
            [8, 'Túi đeo chéo mini da mềm tiện lợi', 320000, 410000, '#tuideocheo #tuimini #phukien'],
            [8, 'Balo thời trang nhiều ngăn đi học đi làm', 480000, 620000, '#balo #tuithoitrang #dailybag'],
            [8, 'Túi xách nữ quai ngắn phối khóa kim loại', 520000, 680000, '#tuixachnu #handbag #thoitrangnu'],
            [9, 'Nón lưỡi trai cotton thêu chữ basic', 150000, 210000, '#nonluoitrai #cap #phukien'],
            [9, 'Thắt lưng da nam mặt khóa kim loại', 260000, 340000, '#thatlung #thatlungda #phukiennam'],
            [9, 'Vớ cổ cao thể thao set 3 đôi', 120000, 170000, '#vo #vothethao #phukien'],
            [9, 'Kính mát gọng vuông chống tia UV', 230000, 310000, '#kinhmat #sunglasses #phukien'],
        ];

        foreach ($products as $index => [$categoryId, $name, $price, $priceSale, $hashtag]) {
            $code = 'GG-SEED-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
            $image = '/storage/products/' . $images[$index % count($images)];
            $slug = Str::slug($name) . '-' . strtolower($code);

            $product = ProductsModel::updateOrCreate(
                ['code' => $code],
                [
                    'category_id' => $categoryId,
                    'name' => $name,
                    'slug' => $slug,
                    'price' => $price,
                    'price_sale' => $priceSale,
                    'import_price' => max(0, $price - 90000),
                    'features' => 1,
                    'meta_title' => $name,
                    'meta_description' => $name . ' tại GlamGo, dễ phối đồ và phù hợp sử dụng hằng ngày.',
                    'attribute_description' => 'Sản phẩm demo được thêm để làm phong phú dữ liệu hiển thị.',
                    'hashtag' => $hashtag,
                    'image' => $image,
                    'image_alt' => $name,
                    'status' => 1,
                    'is_recommen' => $index % 5 === 0 ? 1 : 0,
                ]
            );

            ProductImagesModel::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'image' => $image,
                ],
                [
                    'product_attribute_value_id' => 0,
                    'image_alt' => $name,
                    'status' => 1,
                ]
            );
        }
    }
}
