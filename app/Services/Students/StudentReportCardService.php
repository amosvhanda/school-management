<?php

namespace App\Services\Students;

use App\Models\ExamResult;
use App\Models\Grade;
use App\Models\School;
use App\Models\Student;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentReportCardService
{
    public function download(Student $student, string $format = 'html'): StreamedResponse|Response
    {
        $format = strtolower($format);
        if (! in_array($format, ['html', 'csv'], true)) {
            $format = 'html';
        }

        /*
         * Keep the existing Grade structure.
         * Do NOT assume grades has school_id.
         */
        $grades =
            Grade::query()
                ->where(
                    'student_id',
                    $student->id
                )
                ->orderByDesc('year')
                ->orderBy('term')
                ->orderBy('subject')
                ->get();

        $examResults =
            ExamResult::query()
                ->where(
                    'student_id',
                    $student->id
                )
                ->whereHas(
                    'exam',
                    function ($q) use (
                        $student
                    ) {
                        $q->where(
                            'school_id',
                            $student->school_id
                        )->where(
                            function ($inner) {
                                $inner->where(
                                    'is_published',
                                    true
                                )->orWhereNotNull(
                                    'results_approved_at'
                                );
                            }
                        );
                    }
                )
                ->with([
                    'exam:id,name,exam_date,total_marks,academic_year,term_id',
                    'exam.term:id,name',
                    'subject:id,name',
                ])
                ->orderByDesc(
                    'created_at'
                )
                ->get();

        // Empty grades/results still produce a printable shell (frontend shows clear empty rows).
        // Blocking download made the Student Profile "Download" action appear broken.

        $school =
            School::query()
                ->find(
                    $student->school_id
                );

        $schoolName =
            $school?->name
            ?? 'School';

        $safeName =
            preg_replace(
                '/[^A-Za-z0-9_-]+/',
                '_',
                (string) (
                    $student->full_name
                    ?: 'student'
                )
            )
            ?: 'student';

        $generatedAt =
            now()
                ->timezone('Africa/Harare')
                ->format(
                    'd M Y, H:i'
                );

        if ($format === 'csv') {
            $filename =
                "{$safeName}_results_".
                "{$student->student_number}.csv";

            return response()->streamDownload(
                function () use (
                    $grades,
                    $examResults,
                    $student,
                    $schoolName,
                    $generatedAt
                ) {
                    $out =
                        fopen(
                            'php://output',
                            'w'
                        );

                    fputcsv(
                        $out,
                        [
                            'School',
                            $schoolName,
                        ]
                    );

                    fputcsv(
                        $out,
                        [
                            'Student',
                            $student->full_name,
                        ]
                    );

                    fputcsv(
                        $out,
                        [
                            'Student number',
                            $student->student_number,
                        ]
                    );

                    fputcsv(
                        $out,
                        [
                            'Class',
                            $student->class,
                        ]
                    );

                    fputcsv(
                        $out,
                        [
                            'Generated',
                            $generatedAt,
                        ]
                    );

                    fputcsv(
                        $out,
                        []
                    );

                    fputcsv(
                        $out,
                        [
                            'Report card grades',
                        ]
                    );

                    fputcsv(
                        $out,
                        [
                            'Subject',
                            'Score',
                            'Total',
                            'Grade',
                            'Term',
                            'Year',
                        ]
                    );

                    foreach (
                        $grades as $grade
                    ) {
                        fputcsv(
                            $out,
                            [
                                $grade->subject,
                                $grade->score,
                                $grade->total,
                                $grade->grade,
                                $grade->term,
                                $grade->year,
                            ]
                        );
                    }

                    fputcsv(
                        $out,
                        []
                    );

                    fputcsv(
                        $out,
                        [
                            'Exam results',
                        ]
                    );

                    fputcsv(
                        $out,
                        [
                            'Exam',
                            'Subject',
                            'Exam date',
                            'Marks',
                            'Total',
                            'Percentage',
                            'Grade',
                            'Term',
                            'Year',
                        ]
                    );

                    foreach (
                        $examResults as $result
                    ) {
                        $examDate =
                            $result->exam?->exam_date;

                        fputcsv(
                            $out,
                            [
                                $result->exam?->name,
                                $result->subject?->name,
                                $examDate
                                    ? Carbon::parse(
                                        $examDate
                                    )->format(
                                        'd M Y'
                                    )
                                    : '',
                                $result->marks_obtained,
                                $result->total_marks,
                                $result->percentage,
                                $result->grade,
                                $result->exam?->term?->name,
                                $result->exam?->academic_year,
                            ]
                        );
                    }

                    fclose($out);
                },
                $filename,
                [
                    'Content-Type' => 'text/csv; charset=UTF-8',
                ]
            );
        }

        $html =
            $this->buildReportCardHtml(
                $schoolName,
                $student,
                $grades,
                $examResults,
                $generatedAt
            );

        $filename =
            "{$safeName}_report_card.html";

        return response(
            $html,
            200,
            [
                'Content-Type' => 'text/html; charset=UTF-8',

                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]
        );

    }

    private function buildReportCardHtml(
        string $schoolName,
        Student $student,
        $grades,
        $examResults,
        string $generatedAt,
    ): string {
        $gradeRows =
            $grades
                ->map(
                    function (Grade $grade) {
                        return '<tr>'
                            .'<td>'.
                                e(
                                    (string) (
                                        $grade->subject
                                        ?? '—'
                                    )
                                ).
                            '</td>'

                            .'<td>'.
                                e(
                                    (string) (
                                        $grade->score
                                        ?? '—'
                                    )
                                ).
                            '</td>'

                            .'<td>'.
                                e(
                                    (string) (
                                        $grade->total
                                        ?? '—'
                                    )
                                ).
                            '</td>'

                            .'<td>'.
                                e(
                                    (string) (
                                        $grade->grade
                                        ?? '—'
                                    )
                                ).
                            '</td>'

                            .'<td>'.
                                e(
                                    (string) (
                                        $grade->term
                                        ?? '—'
                                    )
                                ).
                            '</td>'

                            .'<td>'.
                                e(
                                    (string) (
                                        $grade->year
                                        ?? '—'
                                    )
                                ).
                            '</td>'

                            .'</tr>';
                    }
                )
                ->implode('');

        if ($gradeRows === '') {
            $gradeRows =
                '<tr>'.
                '<td colspan="6">'.
                'No report-card grades recorded yet.'.
                '</td>'.
                '</tr>';
        }

        $examRows =
            $examResults
                ->map(
                    function (
                        ExamResult $result
                    ) {
                        $examDate =
                            $result->exam?->exam_date
                                ? Carbon::parse(
                                    $result->exam->exam_date
                                )->format(
                                    'd M Y'
                                )
                                : '—';

                        return '<tr>'
                            .'<td>'.
                                e(
                                    (string) (
                                        $result
                                            ->exam?->name
                                        ?? '—'
                                    )
                                ).
                            '</td>'

                            .'<td>'.
                                e(
                                    (string) (
                                        $result
                                            ->subject?->name
                                        ?? '—'
                                    )
                                ).
                            '</td>'

                            .'<td>'.
                                e($examDate).
                            '</td>'

                            .'<td>'.
                                e(
                                    (string) (
                                        $result
                                            ->marks_obtained
                                        ?? '—'
                                    )
                                ).
                            '</td>'

                            .'<td>'.
                                e(
                                    (string) (
                                        $result
                                            ->total_marks
                                        ?? '—'
                                    )
                                ).
                            '</td>'

                            .'<td>'.
                                e(
                                    (string) (
                                        $result
                                            ->percentage
                                        ?? '—'
                                    )
                                ).
                                '%</td>'

                            .'<td>'.
                                e(
                                    (string) (
                                        $result->grade
                                        ?? '—'
                                    )
                                ).
                            '</td>'

                            .'<td>'.
                                e(
                                    (string) (
                                        $result
                                            ->exam
                                            ?->term
                                            ?->name
                                        ?? '—'
                                    )
                                ).
                            '</td>'

                            .'</tr>';
                    }
                )
                ->implode('');

        if ($examRows === '') {
            $examRows =
                '<tr>'.
                '<td colspan="8">'.
                'No published exam results yet.'.
                '</td>'.
                '</tr>';
        }

        $title =
            e(
                (string) (
                    $student->full_name
                    ?? 'Student'
                )
            ).
            ' — Report card';

        $school =
            e($schoolName);

        $fullName =
            e(
                (string) (
                    $student->full_name
                    ?? '—'
                )
            );

        $studentNumber =
            e(
                (string) (
                    $student->student_number
                    ?? '—'
                )
            );

        $className =
            e(
                (string) (
                    $student->class
                    ?? '—'
                )
            );

        $generated =
            e($generatedAt);

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>{$title}</title>

  <style>
    body {
      font-family: Georgia, "Times New Roman", serif;
      color: #111;
      margin: 32px;
    }

    h1,
    h2 {
      font-family: Arial, Helvetica, sans-serif;
      margin: 0 0 8px;
    }

    .meta {
      color: #444;
      margin-bottom: 24px;
      font-size: 14px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin: 12px 0 28px;
      font-size: 14px;
    }

    th,
    td {
      border: 1px solid #bbb;
      padding: 8px 10px;
      text-align: left;
    }

    th {
      background: #f3f4f6;
      font-family: Arial, Helvetica, sans-serif;
    }

    .footer {
      margin-top: 32px;
      font-size: 12px;
      color: #666;
    }

    @media print {
      body {
        margin: 16px;
      }
    }
  </style>
</head>

<body>

  <h1>{$school}</h1>

  <h2>Student report card</h2>

  <div class="meta">

    <div>
      <strong>Student:</strong>
      {$fullName}
    </div>

    <div>
      <strong>Student number:</strong>
      {$studentNumber}
    </div>

    <div>
      <strong>Class:</strong>
      {$className}
    </div>

    <div>
      <strong>Generated:</strong>
      {$generated}
    </div>

  </div>

  <h2>
    Continuous assessment / report grades
  </h2>

  <table>

    <thead>
      <tr>
        <th>Subject</th>
        <th>Score</th>
        <th>Total</th>
        <th>Grade</th>
        <th>Term</th>
        <th>Year</th>
      </tr>
    </thead>

    <tbody>
      {$gradeRows}
    </tbody>

  </table>

  <h2>Examination results</h2>

  <table>

    <thead>
      <tr>
        <th>Exam</th>
        <th>Subject</th>
        <th>Date</th>
        <th>Marks</th>
        <th>Total</th>
        <th>%</th>
        <th>Grade</th>
        <th>Term</th>
      </tr>
    </thead>

    <tbody>
      {$examRows}
    </tbody>

  </table>

  <p class="footer">
    This document was generated from the school management system.
    Open and use Print → Save as PDF if needed.
  </p>

</body>
</html>
HTML;
    }
}
