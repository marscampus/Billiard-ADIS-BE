<?php

namespace App\Http\Controllers\apimenuresto;

use App\Models\menuresto\Contact;

use Illuminate\Http\Request;
use Schema;

class ContactController extends Controller
{
    function contact(Request $req)
    {
        $contacts = new Contact;
        $contacts->email = trim($req->input('email'), '"');
        $contacts->message = $req->input('message');
        // $contacts ->pictures=$req->input('pictures');
        $contacts->save();
        return $contacts;
    }
    public function index()
    {
        $users = Contact::all();

        return response()->json($users);
    }
    public function destroy($id)
    {
        $contact = Contact::find($id);

        if (!$contact) {
            return response()->json(['message' => 'contact not found'], 404);
        }

        $contact->delete();

        return response()->json(['message' => 'contact deleted successfully'], 200);
    }
    // public function getColumns()
    // {
    //     $columns = Schema::getColumnListing('contacts');
    //     $data = Contact::all();
    //     return response()->json([
    //         'columns' => $columns,
    //         'data' => $data
    //     ]);
    // }
}