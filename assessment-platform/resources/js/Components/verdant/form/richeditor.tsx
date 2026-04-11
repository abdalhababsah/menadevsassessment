import { EditorContent, useEditor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import { cn } from '@/lib/utils';
export function RichEditor({ content, onChange, placeholder, className }: any) {
  const editor = useEditor({ extensions: [StarterKit], content, onUpdate: ({ editor }) => onChange(editor.getHTML()) });
  return <div className={cn("rounded-md border border-stone-200 bg-white p-2 min-h-[150px]", className)}><EditorContent editor={editor} /></div>;
}
