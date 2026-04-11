import { cn } from "@/lib/utils";
export function DataGrid({ data, renderItem, className }: any) {
  return <div className={cn("grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6", className)}>{data.map((item: any, i: number) => renderItem(item, i))}</div>;
}
