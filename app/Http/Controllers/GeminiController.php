<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GeminiController extends Controller
{
    protected GeminiService $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'prompt' => 'required|string|max:2000',
            'context' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => [
                    'ar' => 'بيانات غير صحيحة',
                    'en' => 'Invalid input data',
                ],
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $prompt = $request->input('prompt');
            $context = $request->input('context', []);

            $result = $this->geminiService->search($prompt, $context);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => [
                        'ar' => 'تم الحصول على النتيجة بنجاح',
                        'en' => 'Result retrieved successfully',
                    ],
                    'data' => [
                        'result' => $result['result'],
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => [
                    'ar' => 'فشل في الحصول على النتيجة',
                    'en' => 'Failed to get result',
                ],
                'error' => $result['error'] ?? 'Unknown error',
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => [
                    'ar' => 'حدث خطأ أثناء معالجة الطلب',
                    'en' => 'An error occurred while processing the request',
                ],
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function analyze(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'data_type' => 'required|string|max:100',
            'data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => [
                    'ar' => 'بيانات غير صحيحة',
                    'en' => 'Invalid input data',
                ],
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $dataType = $request->input('data_type');
            $data = $request->input('data');

            $result = $this->geminiService->analyzeData($dataType, $data);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => [
                        'ar' => 'تم تحليل البيانات بنجاح',
                        'en' => 'Data analyzed successfully',
                    ],
                    'data' => [
                        'result' => $result['result'],
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => [
                    'ar' => 'فشل في تحليل البيانات',
                    'en' => 'Failed to analyze data',
                ],
                'error' => $result['error'] ?? 'Unknown error',
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => [
                    'ar' => 'حدث خطأ أثناء معالجة الطلب',
                    'en' => 'An error occurred while processing the request',
                ],
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function generateReport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|array',
            'report_type' => 'sometimes|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => [
                    'ar' => 'بيانات غير صحيحة',
                    'en' => 'Invalid input data',
                ],
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $request->input('data');
            $reportType = $request->input('report_type', 'general');

            $result = $this->geminiService->generateReport($data, $reportType);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => [
                        'ar' => 'تم إنشاء التقرير بنجاح',
                        'en' => 'Report generated successfully',
                    ],
                    'data' => [
                        'result' => $result['result'],
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => [
                    'ar' => 'فشل في إنشاء التقرير',
                    'en' => 'Failed to generate report',
                ],
                'error' => $result['error'] ?? 'Unknown error',
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => [
                    'ar' => 'حدث خطأ أثناء معالجة الطلب',
                    'en' => 'An error occurred while processing the request',
                ],
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function test(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'prompt' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'الرجاء إدخال سؤال',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $prompt = $request->input('prompt');
            $result = $this->geminiService->search($prompt);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'question' => $prompt,
                    'answer' => $result['result'],
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Unknown error',
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

