import { cn, PARCEL_STATUS_COLORS } from '@/lib/utils';

export function StatusBadge({ status }: { status: string }) {
  const classes = PARCEL_STATUS_COLORS[status] ?? 'bg-gray-100 text-gray-600';
  return (
    <span className={cn('inline-block text-xs font-semibold px-2 py-0.5 rounded-full', classes)}>
      {status.replace(/_/g, ' ')}
    </span>
  );
}
