import { useQuery } from '@tanstack/react-query'
import { publicApi } from '@/lib/api'

export function useServices() {
  return useQuery({
    queryKey: ['services'],
    queryFn:  publicApi.getServices,
    staleTime: 5 * 60 * 1000, // 5 min — services don't change often
  })
}
