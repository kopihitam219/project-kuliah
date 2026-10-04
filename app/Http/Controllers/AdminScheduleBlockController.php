<?php

namespace App\Http\Controllers;

use App\Models\ScheduleBlock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminScheduleBlockController extends Controller
{
    /**
     * Daftar jadwal yang ditutup + form tutup jadwal baru.
     */
    public function index(Request $request): View
    {
        $showPast = $request->query('show') === 'past';

        $blocks = ScheduleBlock::query()
            ->when(! $showPast, fn ($q) => $q->upcoming()->orderBy('date')->orderBy('start_time'))
            ->when($showPast, fn ($q) => $q->whereDate('date', '<', today())->orderByDesc('date'))
            ->get()
            ->map(function (ScheduleBlock $block) {
                $block->conflicts = $block->conflictingBookings();
                return $block;
            });

        return view('admin.schedule-blocks.index', [
            'blocks'        => $blocks,
            'showPast'      => $showPast,
            'upcomingCount' => ScheduleBlock::upcoming()->count(),
        ]);
    }

    /**
     * Tutup jadwal baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $allDay = $request->boolean('all_day');

        $validated = $request->validate([
            'date'       => ['required', 'date', 'after_or_equal:today'],
            'all_day'    => ['nullable', 'boolean'],
            'start_time' => [$allDay ? 'nullable' : 'required', 'date_format:H:i'],
            'end_time'   => [$allDay ? 'nullable' : 'required', 'date_format:H:i', $allDay ? 'nullable' : 'after:start_time'],
            'reason'     => ['required', 'string', 'max:150'],
        ], [
            'date.required'          => 'Tanggal wajib diisi.',
            'date.after_or_equal'    => 'Tanggal tidak boleh sebelum hari ini.',
            'start_time.required'    => 'Jam mulai wajib diisi (atau centang "Tutup seharian").',
            'end_time.required'      => 'Jam selesai wajib diisi.',
            'end_time.after'         => 'Jam selesai harus setelah jam mulai.',
            'reason.required'        => 'Alasan wajib diisi, misalnya "Turnamen internal".',
        ]);

        $block = ScheduleBlock::create([
            'date'       => $validated['date'],
            'start_time' => $allDay ? null : $validated['start_time'],
            'end_time'   => $allDay ? null : $validated['end_time'],
            'reason'     => $validated['reason'],
            'created_by' => $request->user()->id,
        ]);

        $conflicts = $block->conflictingBookings()->count();

        $message = 'Jadwal ' . $block->date->locale('id')->translatedFormat('d M Y') . " ({$block->time_label}) berhasil ditutup.";

        if ($conflicts > 0) {
            $message .= " Perhatian: ada {$conflicts} booking aktif di jam tersebut, silakan hubungi customer.";
        }

        return redirect()
            ->route('admin.schedule-blocks.index')
            ->with($conflicts > 0 ? 'error' : 'success', $message);
    }

    /**
     * Batalkan penutupan jadwal.
     */
    public function destroy(ScheduleBlock $scheduleBlock): RedirectResponse
    {
        $scheduleBlock->delete();

        return redirect()
            ->route('admin.schedule-blocks.index')
            ->with('success', 'Penutupan jadwal dibatalkan. Jam tersebut bisa di-booking customer lagi.');
    }
}
