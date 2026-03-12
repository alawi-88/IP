import {
  Modal,
  Input,
  InputNumber,
  Button,
  message,
  Alert,
  Switch,
  Collapse,
  Space,
  Tooltip,
} from 'antd';
import {
  PlusOutlined,
  DeleteOutlined,
  CodeOutlined,
  FormOutlined,
} from '@ant-design/icons';
import { useMutation } from '@tanstack/react-query';
import { useTranslations } from 'next-intl';
import { useState, useCallback, useEffect } from 'react';
import axios from '@/lib/axios';
import { VentureSection } from '@/types/venture';

interface EditSectionModalProps {
  section: VentureSection;
  ventureId: string;
  open: boolean;
  onClose: () => void;
  onSaved: () => void;
}

/* ------------------------------------------------------------------ */
/*  Utilities                                                          */
/* ------------------------------------------------------------------ */

/** Deep clone via JSON */
function deepClone<T>(obj: T): T {
  return JSON.parse(JSON.stringify(obj));
}

/** Set a value at a nested path immutably */
function setAtPath(obj: any, path: (string | number)[], value: any): any {
  if (path.length === 0) return value;
  const [head, ...tail] = path;
  if (typeof head === 'number') {
    const arr = Array.isArray(obj) ? [...obj] : [];
    arr[head] = setAtPath(arr[head], tail, value);
    return arr;
  }
  const clone = obj && typeof obj === 'object' ? { ...obj } : {};
  clone[head] = setAtPath(clone[head], tail, value);
  return clone;
}

/** Delete an item from an array at a path */
function deleteAtPath(obj: any, path: (string | number)[]): any {
  if (path.length === 0) return obj;
  if (path.length === 1) {
    const key = path[0];
    if (typeof key === 'number' && Array.isArray(obj)) {
      const arr = [...obj];
      arr.splice(key, 1);
      return arr;
    }
    const clone = { ...obj };
    delete clone[key as string];
    return clone;
  }
  const [head, ...tail] = path;
  if (typeof head === 'number') {
    const arr = Array.isArray(obj) ? [...obj] : [];
    arr[head] = deleteAtPath(arr[head], tail);
    return arr;
  }
  const clone = { ...obj };
  clone[head] = deleteAtPath(clone[head], tail);
  return clone;
}

/** Add an item to an array at path */
function addToArrayAtPath(obj: any, path: (string | number)[], item: any): any {
  if (path.length === 0) {
    return Array.isArray(obj) ? [...obj, item] : [item];
  }
  const [head, ...tail] = path;
  if (typeof head === 'number') {
    const arr = Array.isArray(obj) ? [...obj] : [];
    arr[head] = addToArrayAtPath(arr[head], tail, item);
    return arr;
  }
  const clone = obj && typeof obj === 'object' ? { ...obj } : {};
  clone[head] = addToArrayAtPath(clone[head], tail, item);
  return clone;
}

/** Convert key names to human-readable labels */
function keyToLabel(key: string): string {
  return key
    .replace(/_/g, ' ')
    .replace(/([a-z])([A-Z])/g, '$1 $2')
    .replace(/\b\w/g, (c) => c.toUpperCase());
}

/** Create a blank item matching the shape of the first item in an array */
function createBlankItem(template: any): any {
  if (typeof template === 'string') return '';
  if (typeof template === 'number') return 0;
  if (typeof template === 'boolean') return false;
  if (Array.isArray(template)) return [];
  if (typeof template === 'object' && template !== null) {
    const blank: any = {};
    for (const k of Object.keys(template)) {
      blank[k] = createBlankItem(template[k]);
    }
    return blank;
  }
  return '';
}

/* ------------------------------------------------------------------ */
/*  Content-aware unwrapper                                            */
/* ------------------------------------------------------------------ */

const KNOWN_WRAPPERS = new Set([
  'text_content', 'stat_cards', 'progress_bars', 'swot_grid',
  'comparison_table', 'pricing_cards', 'journey_timeline',
  'persona_card', 'key_value', 'viability_score', 'cost_table',
  'line_chart', 'bar_chart', 'pie_chart', 'radar_chart',
]);

function unwrapForEditing(content: any): { data: any; wrapperKey: string | null } {
  if (!content || typeof content !== 'object' || Array.isArray(content)) {
    return { data: content, wrapperKey: null };
  }
  const keys = Object.keys(content);
  if (keys.length === 1 && KNOWN_WRAPPERS.has(keys[0])) {
    return { data: content[keys[0]], wrapperKey: keys[0] };
  }
  return { data: content, wrapperKey: null };
}

function rewrapForSaving(data: any, wrapperKey: string | null): any {
  if (wrapperKey) {
    return { [wrapperKey]: data };
  }
  return data;
}

/* ------------------------------------------------------------------ */
/*  Recursive Field Renderer                                           */
/* ------------------------------------------------------------------ */

interface FieldRendererProps {
  label: string;
  value: any;
  path: (string | number)[];
  onChange: (path: (string | number)[], value: any) => void;
  onDelete?: () => void;
  onAddItem?: (path: (string | number)[], item: any) => void;
  onDeleteItem?: (path: (string | number)[]) => void;
  depth?: number;
}

const FieldRenderer = ({
  label,
  value,
  path,
  onChange,
  onDelete,
  onAddItem,
  onDeleteItem,
  depth = 0,
}: FieldRendererProps) => {
  if (value === null || value === undefined) {
    return (
      <div className="mb-3">
        <label className="mb-1 block text-sm font-medium text-gray-700">{label}</label>
        <Input
          value=""
          onChange={(e) => onChange(path, e.target.value)}
          placeholder={`Enter ${label.toLowerCase()}`}
        />
      </div>
    );
  }

  if (typeof value === 'boolean') {
    return (
      <div className="mb-3 flex items-center gap-3">
        <label className="text-sm font-medium text-gray-700">{label}</label>
        <Switch checked={value} onChange={(v) => onChange(path, v)} />
        {onDelete && (
          <Button size="small" danger icon={<DeleteOutlined />} onClick={onDelete} type="text" />
        )}
      </div>
    );
  }

  if (typeof value === 'number') {
    return (
      <div className="mb-3">
        <div className="mb-1 flex items-center justify-between">
          <label className="text-sm font-medium text-gray-700">{label}</label>
          {onDelete && (
            <Button size="small" danger icon={<DeleteOutlined />} onClick={onDelete} type="text" />
          )}
        </div>
        <InputNumber
          value={value}
          onChange={(v) => onChange(path, v ?? 0)}
          className="w-full"
        />
      </div>
    );
  }

  if (typeof value === 'string') {
    const isLong = value.length > 100;
    return (
      <div className="mb-3">
        <div className="mb-1 flex items-center justify-between">
          <label className="text-sm font-medium text-gray-700">{label}</label>
          {onDelete && (
            <Button size="small" danger icon={<DeleteOutlined />} onClick={onDelete} type="text" />
          )}
        </div>
        {isLong ? (
          <Input.TextArea
            value={value}
            onChange={(e) => onChange(path, e.target.value)}
            rows={4}
            placeholder={`Enter ${label.toLowerCase()}`}
          />
        ) : (
          <Input
            value={value}
            onChange={(e) => onChange(path, e.target.value)}
            placeholder={`Enter ${label.toLowerCase()}`}
          />
        )}
      </div>
    );
  }

  if (Array.isArray(value)) {
    // Array of strings
    if (value.length === 0 || typeof value[0] === 'string') {
      return (
        <div className="mb-4">
          <div className="mb-2 flex items-center justify-between">
            <label className="text-sm font-semibold text-gray-700">{label}</label>
            <Button
              size="small"
              type="dashed"
              icon={<PlusOutlined />}
              onClick={() => onAddItem?.(path, '')}
            >
              Add
            </Button>
          </div>
          <div className="space-y-2">
            {value.map((item: string, i: number) => (
              <div key={i} className="flex items-center gap-2">
                <span className="flex-shrink-0 text-xs text-gray-400 w-5 text-right">{i + 1}.</span>
                <Input
                  value={item}
                  onChange={(e) => onChange([...path, i], e.target.value)}
                  className="flex-1"
                />
                <Button
                  size="small"
                  danger
                  icon={<DeleteOutlined />}
                  type="text"
                  onClick={() => onDeleteItem?.([...path, i])}
                />
              </div>
            ))}
          </div>
        </div>
      );
    }

    // Array of numbers
    if (typeof value[0] === 'number') {
      return (
        <div className="mb-4">
          <div className="mb-2 flex items-center justify-between">
            <label className="text-sm font-semibold text-gray-700">{label}</label>
            <Button
              size="small"
              type="dashed"
              icon={<PlusOutlined />}
              onClick={() => onAddItem?.(path, 0)}
            >
              Add
            </Button>
          </div>
          <div className="space-y-2">
            {value.map((item: number, i: number) => (
              <div key={i} className="flex items-center gap-2">
                <span className="flex-shrink-0 text-xs text-gray-400 w-5 text-right">{i + 1}.</span>
                <InputNumber
                  value={item}
                  onChange={(v) => onChange([...path, i], v ?? 0)}
                  className="flex-1"
                />
                <Button
                  size="small"
                  danger
                  icon={<DeleteOutlined />}
                  type="text"
                  onClick={() => onDeleteItem?.([...path, i])}
                />
              </div>
            ))}
          </div>
        </div>
      );
    }

    // Array of objects
    return (
      <div className="mb-4">
        <div className="mb-2 flex items-center justify-between">
          <label className="text-sm font-semibold text-gray-800">{label}</label>
          <Button
            size="small"
            type="dashed"
            icon={<PlusOutlined />}
            onClick={() => {
              const template = value[0] || {};
              onAddItem?.(path, createBlankItem(template));
            }}
          >
            Add Item
          </Button>
        </div>
        <div className="space-y-3">
          {value.map((item: any, i: number) => (
            <div
              key={i}
              className="rounded-lg border border-gray-200 bg-gray-50 p-4"
            >
              <div className="mb-2 flex items-center justify-between">
                <span className="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                  {label.replace(/s$/, '')} {i + 1}
                </span>
                <Button
                  size="small"
                  danger
                  icon={<DeleteOutlined />}
                  type="text"
                  onClick={() => onDeleteItem?.([...path, i])}
                />
              </div>
              {typeof item === 'object' && item !== null ? (
                Object.entries(item).map(([k, v]) => (
                  <FieldRenderer
                    key={k}
                    label={keyToLabel(k)}
                    value={v}
                    path={[...path, i, k]}
                    onChange={onChange}
                    onAddItem={onAddItem}
                    onDeleteItem={onDeleteItem}
                    depth={depth + 1}
                  />
                ))
              ) : (
                <Input
                  value={String(item)}
                  onChange={(e) => onChange([...path, i], e.target.value)}
                />
              )}
            </div>
          ))}
        </div>
      </div>
    );
  }

  // Object
  if (typeof value === 'object') {
    const entries = Object.entries(value);

    if (depth > 0) {
      return (
        <div className="mb-4">
          <Collapse
            size="small"
            items={[
              {
                key: '1',
                label: (
                  <div className="flex items-center justify-between w-full">
                    <span className="text-sm font-semibold">{label}</span>
                    {onDelete && (
                      <Button size="small" danger icon={<DeleteOutlined />} type="text" onClick={(e) => { e.stopPropagation(); onDelete(); }} />
                    )}
                  </div>
                ),
                children: (
                  <div>
                    {entries.map(([k, v]) => (
                      <FieldRenderer
                        key={k}
                        label={keyToLabel(k)}
                        value={v}
                        path={[...path, k]}
                        onChange={onChange}
                        onAddItem={onAddItem}
                        onDeleteItem={onDeleteItem}
                        depth={depth + 1}
                      />
                    ))}
                  </div>
                ),
              },
            ]}
            defaultActiveKey={['1']}
          />
        </div>
      );
    }

    return (
      <div className="space-y-1">
        {entries.map(([k, v]) => (
          <FieldRenderer
            key={k}
            label={keyToLabel(k)}
            value={v}
            path={[...path, k]}
            onChange={onChange}
            onAddItem={onAddItem}
            onDeleteItem={onDeleteItem}
            depth={depth + 1}
          />
        ))}
      </div>
    );
  }

  return null;
};

/* ------------------------------------------------------------------ */
/*  Main Modal Component                                               */
/* ------------------------------------------------------------------ */

export const EditSectionModal = ({
  section,
  ventureId,
  open,
  onClose,
  onSaved,
}: EditSectionModalProps) => {
  const t = useTranslations();
  const [jsonError, setJsonError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isAdvancedMode, setIsAdvancedMode] = useState(false);
  const [jsonText, setJsonText] = useState('');

  // Unwrap content for editing
  const { data: unwrappedData, wrapperKey } = unwrapForEditing(section.content);
  const [content, setContent] = useState<any>(deepClone(unwrappedData));

  // Reset state when section changes
  useEffect(() => {
    const { data } = unwrapForEditing(section.content);
    setContent(deepClone(data));
    setJsonText(JSON.stringify(section.content, null, 2));
    setJsonError(null);
  }, [section.content, open]);

  const updateMutation = useMutation({
    mutationFn: async (contentToSave: any) => {
      await axios.put(
        `/participants/ventures/${ventureId}/sections/${section.id}`,
        { content: contentToSave }
      );
    },
    onSuccess: () => {
      message.success(t('venture.sectionUpdated'));
      setJsonError(null);
      onSaved();
    },
    onError: (error: any) => {
      const errorMessage =
        error.response?.data?.message || t('error.updatingSection');
      message.error(errorMessage);
    },
  });

  const handleFieldChange = useCallback(
    (path: (string | number)[], value: any) => {
      setContent((prev: any) => setAtPath(prev, path, value));
    },
    []
  );

  const handleAddItem = useCallback(
    (path: (string | number)[], item: any) => {
      setContent((prev: any) => addToArrayAtPath(prev, path, item));
    },
    []
  );

  const handleDeleteItem = useCallback(
    (path: (string | number)[]) => {
      setContent((prev: any) => deleteAtPath(prev, path));
    },
    []
  );

  const handleSubmit = async () => {
    try {
      setJsonError(null);
      setIsSubmitting(true);

      let contentToSave: any;

      if (isAdvancedMode) {
        try {
          contentToSave = JSON.parse(jsonText);
        } catch {
          setJsonError(t('venture.invalidJson'));
          return;
        }
      } else {
        contentToSave = rewrapForSaving(content, wrapperKey);
      }

      await updateMutation.mutateAsync(contentToSave);
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleModalClose = () => {
    setJsonError(null);
    setIsAdvancedMode(false);
    onClose();
  };

  const toggleMode = () => {
    if (!isAdvancedMode) {
      // Switching to advanced: sync JSON text from content state
      const fullContent = rewrapForSaving(content, wrapperKey);
      setJsonText(JSON.stringify(fullContent, null, 2));
    } else {
      // Switching back to form: parse JSON text into content state
      try {
        const parsed = JSON.parse(jsonText);
        const { data } = unwrapForEditing(parsed);
        setContent(deepClone(data));
        setJsonError(null);
      } catch {
        setJsonError(t('venture.invalidJson'));
        return;
      }
    }
    setIsAdvancedMode(!isAdvancedMode);
  };

  return (
    <Modal
      title={
        <div className="flex items-center justify-between pr-8">
          <span>{t('venture.editSection')}</span>
          <Tooltip title={isAdvancedMode ? 'Switch to Form Editor' : 'Switch to JSON Editor'}>
            <Button
              size="small"
              type="text"
              icon={isAdvancedMode ? <FormOutlined /> : <CodeOutlined />}
              onClick={toggleMode}
            >
              {isAdvancedMode ? 'Form Mode' : 'Advanced'}
            </Button>
          </Tooltip>
        </div>
      }
      open={open}
      onCancel={handleModalClose}
      footer={[
        <Button key="cancel" onClick={handleModalClose}>
          {t('cancel')}
        </Button>,
        <Button
          key="submit"
          type="primary"
          loading={isSubmitting || updateMutation.isPending}
          onClick={handleSubmit}
        >
          {t('save')}
        </Button>,
      ]}
      width={750}
      styles={{
        body: { maxHeight: '65vh', overflowY: 'auto' },
      }}
    >
      {jsonError && (
        <Alert
          message={jsonError}
          type="error"
          showIcon
          className="mb-4"
          closable
          onClose={() => setJsonError(null)}
        />
      )}

      {isAdvancedMode ? (
        <div>
          <Input.TextArea
            value={jsonText}
            onChange={(e) => setJsonText(e.target.value)}
            rows={16}
            className="font-mono text-sm"
            placeholder={t('venture.enterJsonContent')}
          />
          <p className="mt-2 text-xs text-gray-500">
            {t('venture.editSectionHint')}
          </p>
        </div>
      ) : (
        <div>
          {content !== null && content !== undefined ? (
            <FieldRenderer
              label=""
              value={content}
              path={[]}
              onChange={handleFieldChange}
              onAddItem={handleAddItem}
              onDeleteItem={handleDeleteItem}
              depth={0}
            />
          ) : (
            <Alert
              message="No content available to edit."
              type="info"
              showIcon
            />
          )}
        </div>
      )}
    </Modal>
  );
};
