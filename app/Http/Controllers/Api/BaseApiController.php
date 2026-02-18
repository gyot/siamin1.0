<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

abstract class BaseApiController extends Controller
{
    /**
     * Fully qualified model class name.
     * @var string
     */
    protected $modelClass;

    /**
     * Validation rules for store/update operations.
     * @var array
     */
    protected $rules = [];

    public function index()
    {
        $data = ($this->modelClass)::all();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateIfNeeded($request);
        $item = ($this->modelClass)::create($validated);
        return response()->json(['success' => true, 'data' => $item], 201);
    }

    public function show($id)
    {
        try {
            $item = ($this->modelClass)::findOrFail($id);
            return response()->json(['success' => true, 'data' => $item]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $item = ($this->modelClass)::findOrFail($id);
            $validated = $this->validateIfNeeded($request, $id);
            $item->update($validated);
            return response()->json(['success' => true, 'data' => $item]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }
    }

    public function destroy($id)
    {
        try {
            $item = ($this->modelClass)::findOrFail($id);
            $item->delete();
            return response()->json(['success' => true, 'message' => 'Deleted']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }
    }

    protected function validateIfNeeded(Request $request, $id = null)
    {
        if (empty($this->rules)) {
            // nothing to validate, just return all input
            return $request->all();
        }

        // allow unique rules to ignore current id if provided
        $rules = $this->rules;
        if ($id) {
            foreach ($rules as $field => $rule) {
                if (is_string($rule) && strpos($rule, 'unique:') === 0) {
                    // append ,{$id} to unique rule
                    $rules[$field] = $rule . ',' . $id;
                }
            }
        }

        return $request->validate($rules);
    }
}
