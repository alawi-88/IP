import { Form, Input } from "antd";
import { useTranslations } from "next-intl";
import { useState } from "react";

const EvaluationCommentInput = ({
  name,
  label,
  placeholder,
  maxChars,
  rows,
  className,
  isRequired,
}: {
  name: string;
  label: string;
  placeholder?: string;
  maxChars: number;
  rows: number;
  className?: string;
  isRequired?: boolean;
}) => {
  const t = useTranslations();
  const [charCount, setCharCount] = useState(0);
  const handleChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
    setCharCount(e.target.value.length);
  };

  return (
    <div className="comment__input">
      <Form.Item
        name={name}
        label={label}
        className={`!mb-0 ${className}`}
        rules={[
          {
            required: isRequired ? true : false,
          },
          {
            max: maxChars || undefined,
            message: t("max-length", { value: maxChars }),
          },
        ]}
      >
        <Input.TextArea
          rows={rows}
          maxLength={maxChars ? maxChars : undefined}
          onChange={handleChange}
          showCount={false}
          placeholder={placeholder || label}
        />
      </Form.Item>
      {maxChars ? (
        <div className="text-sm text-gray-400 mt-1">
          {`${charCount} / ${maxChars}`}
        </div>
      ) : null}
    </div>
  );
};

export default EvaluationCommentInput;
