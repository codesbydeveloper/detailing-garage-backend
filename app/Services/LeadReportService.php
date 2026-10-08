<?php

namespace App\Services;

use App\Models\Lead;
use App\Support\Money;

class LeadReportService
{
    public function summarize(?string $from, ?string $to): array
    {
        $query = Lead::query();

        if ($from) {
            $query->whereDate('created_time', '>=', $from);
        }

        if ($to) {
            $query->whereDate('created_time', '<=', $to);
        }

        $byStatus = (clone $query)
            ->join('lead_statuses', 'lead_statuses.id', '=', 'leads.lead_status_id')
            ->selectRaw('lead_statuses.name as label, lead_statuses.slug as slug, COUNT(leads.id) as total')
            ->groupBy('lead_statuses.name', 'lead_statuses.slug')
            ->get();

        $total = (int) $byStatus->sum('total');
        $converted = (int) ($byStatus->firstWhere('slug', 'converted')->total ?? 0);
        $lost = (int) ($byStatus->firstWhere('slug', 'lost')->total ?? 0);
        $rate = $total === 0 ? '0.00' : bcmul(bcdiv((string) $converted, (string) $total, 4), '100', 2);

        return [
            'total_leads' => $total,
            'converted' => $converted,
            'lost' => $lost,
            'conversion_rate' => Money::of($rate),
            'leads_by_status' => $byStatus->map(fn ($row) => [
                'label' => $row->label,
                'slug' => $row->slug,
                'total' => (int) $row->total,
            ])->values(),
            'leads_by_platform' => $platforms = $this->group(clone $query, 'platform'),
            'leads_by_service' => $services = $this->group(clone $query, 'service_interested'),
            'leads_by_campaign' => $this->group(clone $query, 'campaign_name'),
            'by_source' => array_map(fn (array $row) => [
                'name' => $row['label'],
                'value' => $row['total'],
            ], $platforms),
            'by_service' => array_map(fn (array $row) => [
                'name' => $row['label'],
                'value' => $row['total'],
            ], $services),
        ];
    }

    private function group($query, string $column): array
    {
        return $query
            ->selectRaw("COALESCE({$column}, 'Unknown') as label, COUNT(*) as total")
            ->groupBy('label')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'total' => (int) $row->total])
            ->all();
    }
}
