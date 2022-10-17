<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Shop;
use App\Models\SecondaryCategory;
use App\Models\Image;
use App\Models\Stock;
use App\Models\User;

use function PHPUnit\Framework\isNull;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'shop_id',
        'name',
        'information',
        'price',
        'is_selling',
        'sort_order',
        'secondary_category_id',
        'image1',
        'image2',
        'image3',
        'image4',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function category()
    {
        return $this->belongsTo(SecondaryCategory::class, 'secondary_category_id');
    }

    public function imageFirst()
    {
        return $this->belongsTo(Image::class, 'image1', 'id');
    }

    public function imageSecond()
    {
        return $this->belongsTo(Image::class, 'image2', 'id');
    }

    public function imageThird()
    {
        return $this->belongsTo(Image::class, 'image3', 'id');
    }

    public function imageFourth()
    {
        return $this->belongsTo(Image::class, 'image4', 'id');
    }

    public function stock()
    {
        return $this->hasMany(Stock::class);
    }

    public function users() // 中間テーブル
    {
        return $this->belongsToMany(User::class, 'carts')
            ->withPivot(['id', 'quantity', 'is_paying']);
    }

    public function scopeAvailableItems($query)
    {
        $stocks = DB::table('t_stocks')
            ->select('product_id', DB::raw('sum(quantity) as quantity'))
            ->groupBy('product_id')
            ->having('quantity', '>=', 1);

        return $query
            ->select('products.id', 'products.name', 'products.price', 'products.sort_order',
                'products.information', 'secondary_categories.name as category', 'image1.filename as filename1')
            ->joinSub($stocks, 'stock', function($join) {
                $join->on('products.id', '=', 'stock.product_id');
            })
            ->join('shops', 'products.shop_id', '=', 'shops.id')
            ->join('secondary_categories', 'products.secondary_category_id', '=', 'secondary_categories.id')
            ->join('images as image1', 'products.image1', '=', 'image1.id')
            ->where('shops.is_selling', true)
            ->where('products.is_selling', true);
    }

    public function scopeSortOrder($query, $sordOrder)
    {
        if(is_null($sordOrder) || $sordOrder === \Constant::SORT_ORDER['recommend'] ) return $query->orderBy('sort_order', 'asc');
        if($sordOrder === \Constant::SORT_ORDER['higherPrice'] ) return $query->orderBy('price', 'desc');
        if($sordOrder === \Constant::SORT_ORDER['lowerPrice'] ) return $query->orderBy('price', 'asc');
        if($sordOrder === \Constant::SORT_ORDER['later'] ) return $query->orderBy('products.created_at', 'desc');
        if($sordOrder === \Constant::SORT_ORDER['older'] ) return $query->orderBy('products.created_at', 'asc');
    }

}
