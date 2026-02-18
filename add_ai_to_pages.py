import os
import re

BASE = "/Users/alawialjifri/IP/Frontend/src/app/[locale]/participant-dashboard/startups/[startupId]"

pages_without_ai = [
    "competitive-analysis/competitors",
    "competitive-analysis/matrix",
    "gtm-strategy/customer-segments",
    "gtm-strategy/overview",
    "gtm-strategy/value-proposition",
    "path-to-mvp/budget",
    "path-to-mvp/features",
    "path-to-mvp/marketing",
    "path-to-mvp/metrics",
    "path-to-mvp/timeline",
    "path-to-mvp/validation",
    "strategic-frameworks/bmc",
    "strategic-frameworks/business-plan",
    "strategic-frameworks/gtm-overview",
    "strategic-frameworks/mvp-canvas",
    "strategic-frameworks/pestel",
]

for page_path in pages_without_ai:
    filepath = os.path.join(BASE, page_path, "page.tsx")
    if not os.path.exists(filepath):
        print(f"SKIP (not found): {page_path}")
        continue
    
    with open(filepath, 'r') as f:
        content = f.read()
    
    if 'AiGenerateButton' in content:
        print(f"SKIP (already has AI): {page_path}")
        continue
    
    parts = page_path.split('/')
    section = parts[0]
    page_name = parts[1]
    
    # 1. Add AiGenerateButton import
    content = content.replace(
        'import VaPageLayout from "@/components/va/VaPageLayout";',
        'import VaPageLayout from "@/components/va/VaPageLayout";\nimport AiGenerateButton from "@/components/va/AiGenerateButton";'
    )
    
    # 2. Add AI mutation and handlers
    ai_block = '''
  const aiGenerateMutation = useMutation({
    mutationFn: (data: any) =>
      startupApi.generateAi(
        startupId,
        "''' + section + '''",
        "''' + page_name + '''",
        data.fieldKey,
        data.prompt
      ),
  });

  const handleAiGenerate = useCallback(
    async (fieldKey: string, prompt: string) => {
      try {
        const result = await aiGenerateMutation.mutateAsync({
          fieldKey,
          prompt,
        });
        return result.generatedContent;
      } catch (error) {
        throw error;
      }
    },
    [aiGenerateMutation]
  );

  const handleAiAccept = useCallback(
    (fieldKey: string, content: string) => {
      form.setFieldValue(fieldKey, content);
      const values = form.getFieldsValue();
      updateMutation.mutate(values);
    },
    [form, updateMutation]
  );'''
    
    # Insert after completeMutation
    cmp = re.search(r'(const completeMutation = useMutation\(\{[\s\S]*?\}\);)', content)
    if cmp:
        pos = cmp.end()
        content = content[:pos] + '\n' + ai_block + content[pos:]
    else:
        upd = re.search(r'(const updateMutation = useMutation\(\{[\s\S]*?\}\);)', content)
        if upd:
            pos = upd.end()
            content = content[:pos] + '\n' + ai_block + content[pos:]
    
    # 3. Add useCallback if missing
    if 'useCallback' not in content:
        content = content.replace(
            'import { useState } from "react";',
            'import { useState, useCallback } from "react";'
        )
    
    # 4. Add AiGenerateButton to Form.Item labels
    # Pattern: name="fieldName"\n          label={t("va.xxx", "Yyy")}
    def replace_label(m):
        name = m.group(1)
        label_call = m.group(2)
        return 'name="' + name + '"\n          label={<div className="flex items-center justify-between w-full"><span>{' + label_call + '}</span><AiGenerateButton fieldLabel={' + label_call + '} onGenerate={(prompt) => handleAiGenerate("' + name + '", prompt)} onAccept={(content) => handleAiAccept("' + name + '", content)} /></div>}'
    
    content = re.sub(r'name="([^"]+)"\s*\n\s*label=\{(t\([^)]+\))\}', replace_label, content)
    
    # Also handle label="string" pattern
    def replace_label2(m):
        name = m.group(1)
        label_text = m.group(2)
        return 'name="' + name + '"\n          label={<div className="flex items-center justify-between w-full"><span>' + label_text + '</span><AiGenerateButton fieldLabel="' + label_text + '" onGenerate={(prompt) => handleAiGenerate("' + name + '", prompt)} onAccept={(content) => handleAiAccept("' + name + '", content)} /></div>}'
    
    content = re.sub(r'name="([^"]+)"\s*\n\s*label="([^"]+)"', replace_label2, content)
    
    with open(filepath, 'w') as f:
        f.write(content)
    
    print(f"UPDATED: {page_path}")

print("\nDone!")
