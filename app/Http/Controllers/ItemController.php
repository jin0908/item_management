<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\Item;


class ItemController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * 商品一覧
     */
    public function index()
    {
        // 商品一覧取得
        $items = Item
            ::where('items.status', 'active')
            ->select()
            ->get();

        return view('item.index', compact('items'));
    }

    
    /**
     * 商品登録
     */
    public function add(Request $request)
    {
        // POSTリクエストのとき
        if ($request->isMethod('post')) {
            // バリデーション
            $this->validate($request, [
                'name' => 'required|max:100',
                'kana_name' => 'required|max:100|regex:/^[ァ-ヴー]+$/u',
                'quantity' => 'required|numeric|max:100000',
                'type' => 'required',
                ]);

        /*
        // リクエストされたデータを$formに格納する
        $form = $request->all();
        //formにファイルが存在していたら   
        if(isset($form['image'])){
        // $fileにイメージデータを格納する
        $file = $request->file('image');
        // getClientOriginalExtension()でファイルの拡張子を取得する
        $extension = $file->getClientOriginalExtension();
        //32文字のランダムの文字列を作成
        $file_token = Str::random(32);
        //$file_tokenと$extensionを$filenameに格納
        $filename = $file_token . '.' . $extension;
        // 表示を行うときに画像名が必要になるため、ファイル名を再設定
        $form['image'] = $filename;
        //move('指定したファイル', 保存するファイル)で、Publicフォルダ内に指定したファイルが作られ、アップロードした画像が保存される
        //$file->move('uploads/items', $filename);

        //Storageフォルダに画像を保存
        //$dir = 'image_name';
        //Storage::disk('public')->putFileAs('dir', $filename);

        }else{
            //formにファイルが無ければ何も保存しない
            $filename = null;
        }
        */    

        //storageへ画像を保存するパターン
        if ($request->hasFile('image')) {
            // ディレクトリ名
            $dir = 'image';

            // アップロードされたファイル名を取得
            $file_name = $request->file('image')->getClientOriginalName();

            // 取得したファイル名で保存
            $request->file('image')->storeAs('public/' . $dir, $file_name);

            $path = 'storage/' .$dir . '/' . $file_name;
        }else{
            $path = "";
            
        }
            
            // 商品登録
            Item::create([
                'user_id' => Auth::user()->id,
                'name' => $request->name,
                'kana_name' => $request->kana_name,
                'quantity' => $request->quantity,
                'type' => $request->type,
                'image' => $path,
                'detail' => $request->detail,
            ]);

            return redirect('/items');
        }

        return view('item.add');
    }

    /**
     * 商品検索
     */
    public function search(Request $request)
    {
        // 検索フォームで入力された値を取得する
        $search =$request->get('search');
        
        //入力されたクエリストリングのみ取得
        $query = Item::query();

        if(!empty($search))
        {
            $query->where('name','LIKE',"%{$search}%");
        }

        $items = $query->get();

        return view('item.index',[
            'items'=>$items,  
        ]);
        
    }

    /**
     * 商品並び替え
     */
    public function sort(Request $request)
    {
        //itemをソートで取得
        $sort = $request->get('sort');

        //ID順
        if($sort){
            if($sort === 'ID'){
                $items = Item::orderBy('ID', 'asc')->get();
        
        //名前順
        } elseif ($sort === 'kana_name'){
                $items = Item::orderBy('kana_name')->get();

        //個数（昇順）
        } elseif ($sort === 'asc') {
                $items = Item::orderBy('quantity',  'asc')->get();

        //個数（降順）
        } elseif ($sort === 'desc'){
            $items = Item::orderBy('quantity', 'desc')->get();    
        
         //登録日順
        } elseif ($sort === 'created_at'){
            $items = Item::orderBy('created_at','desc')->get();

        //更新日順
        }else {
            $items = Item::orderBy('updated_at','desc')->get();
        }       
        
        return view('item.index',[
            'items'=>$items,  
        ]);
        
    }
    }
    /**
     * 商品編集画面を表示
     */
    public function edit($id)
    {

    //該当するIDのアイテムを取得
    $item = Item::find($id);
    
        return view('item.update',[
        'item' => $item,   
        ]);
    }
    
    /**
     * 商品編集
     */
    public function update(Request $request) 
    {
        $request->validate([
            'name' => 'required|max:100',
            'kana_name' => 'required|max:100|regex:/^[ァ-ヴー]+$/u',
            'quantity' => 'required|numeric|max:100000',
            'type' => 'required',
            
        ]);

        // 新たな画像ファイルの文字列データ取得
        $image = $request->file('image');
        //現在のファイルのデータを取得
        $item = Item::where('id', '=', $request->id)->first();
        //ファイルの文字列データを「/」で分ける
        $str = explode("/", $item->image);
        //dd($str);
        //ファイル文字列データを格納
        $file_name = $str[2];
        //画像を変更する場合、現在のファイルを削除
        //dd($file_name);
        if (isset($image)) {
        Storage::disk('public')->delete('image/' . $file_name);
        
        //新たにアップロードされたファイルデータを取得
        /*$file = $request->file('image');
        // getClientOriginalExtension()でファイルの拡張子を取得する
        $extension = $file->getClientOriginalExtension();
        //32文字のランダムの文字列を作成
        $file_token = Str::random(32);
        //$file_tokenと$extensionを$filenameに格納
        $filename = $file_token . '.' . $extension;
        // 表示を行うときに画像名が必要になるため、ファイル名を再設定
        $form['image'] = $filename;
        //move('指定したファイル', 保存するファイル)で、Publicフォルダ内に指定したファイルが作られ、アップロードした画像が保存される
        $file->move('uploads/items', $filename);

        }
        */
            // ディレクトリ名
            $dir = 'image';

            // アップロードされたファイル名を取得
            $file_name = $request->file('image')->getClientOriginalName();

            // 取得したファイル名で保存
            $request->file('image')->storeAs('public/' . $dir, $file_name);

            $path = 'storage/' .$dir . '/' . $file_name;

        }else{
            $path = $item->image;
        }

        //商品編集するため、リクエストで渡されたIDを元にデータを取得
        $item = Item::where('id', $request->id)->first();
            $item->name = $request->name;
            $item->kana_name = $request->kana_name;
            $item->quantity = $request->quantity;
            $item->type = $request->type;
            /*if (isset($filename)) {
                $item->image = $filename;
            }*/
            $item->image = $path;
            $item->detail = $request->detail;
            $item->save();
        
        
            return redirect('/items');
        
    }

    /**
     * 削除機能
     */
    public function delete(Request $request)
    {
    //既存のレコード取得
    $item = Item::where('id', $request->id)->first();
    

    //画像削除
    //ファイルの文字列データを「/」で分ける
    $str = explode("/", $item->image);
    //dd($str);
    //ファイル文字列データを格納
    $file_name = $str[2];
    //画像データを削除
    Storage::disk('public')->delete('image/' . $file_name);
    
    //データを削除
    $item->delete();

    return redirect('/items');
    }
}


