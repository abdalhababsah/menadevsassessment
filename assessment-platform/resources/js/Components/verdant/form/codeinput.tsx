import Editor from "@monaco-editor/react";
export function CodeInput({ value, onChange, language = "javascript", height = "300px" }: any) {
  return (
    <div className="border border-stone-200 rounded-xl overflow-hidden">
      <Editor height={height} language={language} value={value} onChange={onChange} options={{ minimap: { enabled: false }, automaticLayout: true }} />
    </div>
  );
}
