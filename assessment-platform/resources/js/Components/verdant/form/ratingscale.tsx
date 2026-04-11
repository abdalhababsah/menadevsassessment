import { cn } from "@/lib/utils";
export function RatingScale({ max = 5, min = 1, value, onChange, className }: any) {
  const steps = Array.from({ length: max - min + 1 }, (_, i) => min + i);
  return (
    <div className={cn("flex gap-2", className)}>
      {steps.map((step) => (
        <button
          key={step}
          type="button"
          onClick={() => onChange(step)}
          className={cn("w-10 h-10 rounded-full border text-sm font-medium transition-all", 
            value === step ? "bg-verdant-500 text-white border-verdant-500" : "bg-white text-stone-500 border-stone-200 hover:border-verdant-200"
          )}
        >
          {step}
        </button>
      ))}
    </div>
  );
}
