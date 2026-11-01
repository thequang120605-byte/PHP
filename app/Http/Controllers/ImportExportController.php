<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\ImportedFile;
use App\Models\Semester;
use App\Services\ImportService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImportExportController extends Controller
{
    public function __construct(
        protected ImportService $importService,
        protected ReportService $reportService
    ) {}

    /**
     * Giao diện import danh sách đăng ký cho sự kiện
     */
    public function showImportPage(Event $event)
    {
        $pastImports = ImportedFile::where('event_id', $event->id)
            ->with('user')
            ->latest()
            ->get();

        return view('admin.import', compact('event', 'pastImports'));
    }

    /**
     * Preview dữ liệu trước khi import
     */
    public function previewImport(Request $r, Event $event)
    {
        $r->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $r->file('file');
        $preview = $this->importService->previewCsv($file, $event);

        // Lưu tạm file vào session để commit sau
        $tempPath = $file->store('temp_imports');
        session([
            'import_temp_path' => $tempPath,
            'import_event_id' => $event->id,
            'import_actor_id' => $r->user()->id,
            'import_expires_at' => now()->addMinutes(20)->timestamp,
            'import_file_name' => $file->getClientOriginalName(),
            'import_valid_rows' => array_values(array_filter($preview['rows'], fn ($x) => $x['is_valid'])),
        ]);

        return view('admin.import-preview', compact('event', 'preview'));
    }

    /**
     * Xác nhận commit dữ liệu sau khi preview
     */
    public function commitImport(Request $r, Event $event)
    {
        abort_unless(session('import_event_id') === $event->id && session('import_actor_id') === $r->user()->id && (int) session('import_expires_at') > now()->timestamp, 422, 'Phiên import hết hạn hoặc không thuộc sự kiện này.');
        $tempPath = session('import_temp_path');
        $fileName = session('import_file_name');
        $validRows = session('import_valid_rows', []);

        if (! $tempPath || ! Storage::exists($tempPath)) {
            return redirect("/admin/events/{$event->id}/import")->withErrors(['import' => 'Phiên xem trước đã hết hạn. Vui lòng tải lại file.']);
        }

        $fullPath = Storage::path($tempPath);
        $uploadedFile = new UploadedFile($fullPath, $fileName, null, null, true);

        $res = $this->importService->commitRegistrations($event, $validRows, $uploadedFile, $r->user());

        // Xóa tạm
        Storage::delete($tempPath);
        session()->forget(['import_temp_path', 'import_file_name', 'import_valid_rows', 'import_event_id', 'import_actor_id', 'import_expires_at']);

        return redirect("/admin/events/{$event->id}/participants")
            ->with('success', "Đã import thành công {$res->success_rows} đăng ký vào sự kiện. File nguồn đã được lưu trữ đối soát.");
    }

    /**
     * Xuất file CSV đối soát chi tiết UTF-8 BOM
     */
    public function exportReconciliation(Event $event)
    {
        return $this->reportService->exportEventReconciliationCsv($event);
    }

    /**
     * Báo cáo thống kê theo sự kiện và học kỳ
     */
    public function reports(Request $r)
    {
        $semesterId = $r->input('semester_id');
        $semesters = Semester::orderBy('id', 'desc')->get();
        $selectedSemester = $semesterId ? Semester::find($semesterId) : $semesters->first();

        $events = Event::when($selectedSemester, fn ($q) => $q->where('semester_id', $selectedSemester->id))
            ->with(['category', 'criteria', 'semester'])
            ->withCount(['confirmed', 'waitlist'])
            ->get();

        $eventReports = [];
        foreach ($events as $e) {
            $eventReports[] = $this->reportService->getEventReport($e);
        }

        $semesterReport = $selectedSemester ? $this->reportService->getSemesterReport($selectedSemester) : null;

        return view('admin.reports', compact('semesters', 'selectedSemester', 'eventReports', 'semesterReport'));
    }
}
