import {
  Input,
  DatePicker,
  TimePicker,
  Select,
  Radio,
  Checkbox,
  Upload,
  Button,
  InputNumber,
} from "antd";
import { useLocale, useTranslations } from "next-intl";
import { Field } from "@/lib/interfaces";
const { Option } = Select;

export function useRenderFieldType() {
  const t = useTranslations();
  const locale = useLocale();

  const acceptNumbers = (
    e: React.FormEvent<HTMLInputElement> & { data: string }
  ) => {
    const char = e.data;
    if (char && !/^[0-9]$/.test(char)) {
      e.preventDefault();
    }
  };

  function renderFieldType(
    field: Field,
    helperFunction?: any,
    isDisabled?: any,
    readOnly?: any
  ) {
    const commonProps = {
      placeholder: field.placeholder || field.label,
      disabled: isDisabled,
      readOnly,
      ...(helperFunction || {}),
    };

    switch (field.type) {
      case "text":
      case "email":
      case "url":
        return <Input type="text" {...commonProps} />;
      case "phone":
        return (
          <Input type="tel" onBeforeInput={acceptNumbers} {...commonProps} />
        );
      case "number":
        return <InputNumber className="w-full" {...commonProps} />;
      case "textarea":
        return <Input.TextArea rows={4} {...commonProps} />;
      case "date":
        return (
          <DatePicker
            style={{ width: "100%" }}
            format="YYYY-MM-DD"
            getPopupContainer={(trigger) =>
              trigger.closest(".dashboard-card") || document.body
            }
            {...commonProps}
          />
        );
      case "time":
        return (
          <TimePicker
            style={{ width: "100%" }}
            use12Hours
            format="h:mm A"
            {...commonProps}
          />
        );
      case "dropdown":
        return (
          <Select
            showSearch={true}
            filterOption={(input, option) => {
              const label = option?.children?.toString()?.toLowerCase() || "";
              return label.includes(input.toLowerCase());
            }}
            notFoundContent={null}
            allowClear={true}
            style={{ width: "100%" }}
            {...commonProps}
          >
            {field?.options?.map((opt, index) => (
              <Option key={index} value={opt.id.toString()}>
                {opt.label}
              </Option>
            ))}
          </Select>
        );
      case "multi_select":
        return (
          <Select
            mode="multiple"
            showSearch={true}
            filterOption={(input, option) => {
              const label = option?.children?.toString()?.toLowerCase() || "";
              return label.includes(input.toLowerCase());
            }}
            notFoundContent={null}
            allowClear={true}
            {...commonProps}
          >
            {field?.options?.map((opt, index) => (
              <Option key={index} value={opt.id.toString()}>
                {opt.label}
              </Option>
            ))}
          </Select>
        );
      case "team":
        return (
          <Select
            mode="tags"
            notFoundContent={null}
            open={false}
            suffixIcon={null}
            showSearch={false}
            tokenSeparators={[","]}
            allowClear
            {...commonProps}
          />
        );
      case "radio":
        return (
          <Radio.Group
            className="checkbox-group !grid lg:grid-cols-2 !gap-4"
            {...commonProps}
          >
            {field?.options?.map((opt, index) => (
              <Radio key={index} value={opt.id.toString()}>
                {opt.label}
              </Radio>
            ))}
          </Radio.Group>
        );
      case "checkbox":
        return (
          // <Checkbox.Group
          //   className="checkbox-group !grid lg:grid-cols-2 !gap-4"
          //   options={
          //     field.options?.split(",")?.map((item: any) => item.trim()) || []
          //   }
          //   {...commonProps}
          // />
          <Checkbox.Group
            className="checkbox-group !grid lg:grid-cols-2 !gap-4"
            {...commonProps}
          >
            {field?.options?.map((opt, index) => (
              <Checkbox key={index} value={opt.id.toString()}>
                {opt.label}
              </Checkbox>
            ))}
          </Checkbox.Group>
        );
      case "rating":
        return (
          <Radio.Group
            rootClassName="!flex !gap-4 !flex-wrap !items-center"
            {...commonProps}
          >
            {field?.options?.map((opt, index) => (
              <Radio.Button
                key={index}
                value={opt.id.toString()}
                rootClassName="evaluate"
              >
                {opt.label}
              </Radio.Button>
            ))}
          </Radio.Group>
        );
      case "file":
        const allowedMimesRule = field.validation_rules?.find(
          (rule) => rule.rule === "mimes"
        );

        const allowedMimes = allowedMimesRule?.allowed_mimes_string
          ?.split(",")
          .map((mime) => mime.trim().toLowerCase())
          .map((ext) => (ext.startsWith(".") ? ext : `.${ext}`))
          .join(",");

        return (
          <Upload
            className="flex items-center gap-x-3 bg-[#F2F4F7]"
            multiple={false}
            maxCount={1}
            beforeUpload={() => false}
            accept={allowedMimes || undefined}
            {...commonProps}
          >
            <Button disabled={commonProps.disabled} type="primary">
              {t("select-file")}
            </Button>
          </Upload>
        );

      case "section_header":
        return <div className="section_header">{field.label}</div>;
      case "paragraph":
        return (
          <p className="section_paragraph text-base text-gray-500">
            {field.label}
          </p>
        );
      default:
        return <Input {...commonProps} />;
    }
  }

  return { renderFieldType };
}
