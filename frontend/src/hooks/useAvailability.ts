import { useQuery } from '@tanstack/react-query'
import { publicApi } from '@/lib/api'

export function useAvailableDates(serviceId: number | null, staffId?: number) {
  return useQuery({
    queryKey:  ['availability', 'dates', serviceId, staffId],
    queryFn:   () => publicApi.getAvailableDates(serviceId!, staffId),
    enabled:   !!serviceId,
    staleTime: 60 * 1000, // 1 min
  })
}

export function useSlots(
  serviceId: number | null,
  staffId:   number | undefined,
  date:      string | null,
) {
  return useQuery({
    queryKey:  ['availability', 'slots', serviceId, staffId, date],
    queryFn:   () => publicApi.getSlots(serviceId!, staffId, date!),
    enabled:   !!serviceId && !!date,
    staleTime: 30 * 1000, // 30 sec — slots change quickly
  })
}
