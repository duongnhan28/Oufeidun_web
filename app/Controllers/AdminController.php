<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Auth;use App\Core\Database;use App\Repositories\ProductRepository;
final class AdminController
{
    public function index():void{$user=Auth::requireUser();view('admin/dashboard',['title'=>'Quản trị - Oufeidun','user'=>$user,'products'=>(new ProductRepository())->all(true)],'layouts/admin');}
    public function lookup():void{$user=Auth::requireUser();$items=[];try{$items=Database::connection()->query('SELECT p.id,p.sku,p.name,p.description,p.active,p.source,p.updated_at,(SELECT COUNT(*) FROM glass_lookup_models m WHERE m.product_id=p.id) model_count,(SELECT COUNT(*) FROM glass_lookup_images i WHERE i.product_id=p.id) image_count FROM glass_lookup_products p ORDER BY p.updated_at DESC,p.sku')->fetchAll();}catch(\Throwable){}view('admin/lookup',['title'=>'Quản lý mã kính - Oufeidun','user'=>$user,'items'=>$items],'layouts/admin');}
    public function messages():void{$user=Auth::requireUser();$messages=[];try{$messages=Database::connection()->query('SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 200')->fetchAll();}catch(\Throwable){}view('admin/messages',['title'=>'Tin nhắn - Oufeidun','user'=>$user,'messages'=>$messages],'layouts/admin');}
}

