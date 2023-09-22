<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ExcelController extends Controller
{

    public function index(){

        return view('upload');
    }

    public function import(Request $request)
    {
        // Validate the uploaded file
        $request->validate([
            'file' => 'required|mimes:xlsx',
        ]);

        // Store the uploaded file
        $file = $request->file('file');
        $filePath = Storage::putFile('uploads', $file);

        // Use Laravel Excel to read the uploaded Excel file
        $data = Excel::toArray([], $filePath);

        // Pass data to the view
        $headers = $data[0][0];

        // Use Laravel's Schema Builder to dynamically create a new table
        Schema::create('dynamic_table', function ($table) use ($headers) {
            $table->id();
            foreach ($headers as $header) {
                $table->string($header)->nullable();
            }
            $table->timestamps();
        });

        foreach (array_slice($data[0], 1) as $row) {
            DB::table('dynamic_table')->insert(array_combine($headers, $row));
        }

        return view('display', [
            'data' => $data[0], // Assuming the first sheet contains data
            'headers' => $headers, // Assuming the first row contains headers
        ]);
    }
}
