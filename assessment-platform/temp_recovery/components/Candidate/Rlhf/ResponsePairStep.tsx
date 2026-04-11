import { Stack } from "../../verdant/Layout/Stack";
import { LoadingShimmer } from "../../verdant/Feedback/LoadingShimmer";
export function ResponsePairStep({ turn }: any) {
  if (!turn.response_a || !turn.response_b) {
    return (
      <Stack gap="comfortable">
        <div className="flex items-center gap-2">
           <div className="animate-spin h-4 w-4 border-2 border-verdant-500 border-t-transparent rounded-full" />
           <p className="text-stone-500 font-medium">Generating model responses...</p>
        </div>
        <div className="grid grid-cols-2 gap-4">
           <LoadingShimmer className="h-64 rounded-2xl" />
           <LoadingShimmer className="h-64 rounded-2xl" />
        </div>
      </Stack>
    );
  }
  return (
    <div className="grid grid-cols-2 gap-4">
       <div className="p-4 rounded-2xl border border-stone-200 bg-white">
         <p className="text-xs font-bold uppercase tracking-widest text-stone-400 mb-2">Response A</p>
         <div className="text-sm leading-relaxed text-stone-700 whitespace-pre-wrap">{turn.response_a}</div>
       </div>
       <div className="p-4 rounded-2xl border border-stone-200 bg-white">
         <p className="text-xs font-bold uppercase tracking-widest text-stone-400 mb-2">Response B</p>
         <div className="text-sm leading-relaxed text-stone-700 whitespace-pre-wrap">{turn.response_b}</div>
       </div>
    </div>
  );
}
