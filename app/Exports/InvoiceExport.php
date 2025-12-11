<?php

namespace App\Exports;

use App\Models\Invoice;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class InvoiceExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected Collection $invoices;

    public function __construct(Collection $invoices)
    {
        $this->invoices = $invoices;
    }

    public function collection()
    {
        return $this->invoices;
    }

    public function headings(): array
    {
        return [
            'Invoice Number',
            'Date',
            'Due Date',
            'Customer',
            'Status',
            'Subtotal (KES)',
            'Tax (KES)',
            'Total (KES)',
            'Outstanding (KES)',
            'Payment Status'
        ];
    }

    public function map($invoice): array
    {
        $outstanding = $invoice->getOutstandingAmount();
        $paymentStatus = $outstanding <= 0 ? 'Paid' : ($outstanding < $invoice->total ? 'Partial' : 'Unpaid');

        return [
            $invoice->invoice_number,
            $invoice->invoice_date->format('d/m/Y'),
            $invoice->due_date ? $invoice->due_date->format('d/m/Y') : 'N/A',
            $invoice->contact->name,
            ucfirst($invoice->status),
            number_format($invoice->subtotal, 2),
            number_format($invoice->tax_amount, 2),
            number_format($invoice->total, 2),
            number_format($outstanding, 2),
            $paymentStatus
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E5E7EB']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Invoices';
    }
}
