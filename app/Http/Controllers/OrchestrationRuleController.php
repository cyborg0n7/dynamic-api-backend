<?php

namespace App\Http\Controllers;
use App\Models\OrchestrationRule;

use Illuminate\Http\Request;

class OrchestrationRuleController extends Controller
{
    public function index()
    {
        return response()->json(OrchestrationRule::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'api_id' => 'required|exists:apis,id',
            'rule_name' => 'required|string',
            'condition' => 'required|array',
            'action' => 'required|array'
        ]);

        $rule = OrchestrationRule::create([
            'api_id' => $request->api_id,
            'rule_name' => $request->rule_name,
            'condition' => $request->condition,
            'action' => $request->action
        ]);

        return response()->json($rule, 201);
    }

    public function show($id)
    {
        return response()->json(OrchestrationRule::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $rule = OrchestrationRule::findOrFail($id);

        $rule->update($request->all());

        return response()->json($rule);
    }

    public function destroy($id)
    {
        OrchestrationRule::destroy($id);

        return response()->json(['message' => 'Rule deleted']);
    }
}
