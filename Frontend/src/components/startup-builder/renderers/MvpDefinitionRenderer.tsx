"use client";

interface Props {
  content: any;
}

export default function MvpDefinitionRenderer({ content }: Props) {
  const coreConcept = content.core_concept || content.description || content.summary;
  const features = content.must_have_features || content.features || content.items || [];
  const successCriteria = content.success_criteria || content.metrics;

  return (
    <div className="space-y-5">
      {coreConcept && (
        <div>
          <h4 className="text-sm font-semibold text-gray-800 uppercase tracking-wider mb-2">Core Concept</h4>
          <p className="text-sm text-gray-600 leading-relaxed">{coreConcept}</p>
        </div>
      )}

      {features.length > 0 && (
        <div>
          <h4 className="text-sm font-semibold text-gray-800 uppercase tracking-wider mb-3">
            Must-Have Features ({features.length})
          </h4>
          <div className="space-y-2">
            {features.map((f: any, i: number) => (
              <div key={i} className="flex items-start gap-3 bg-gray-50 rounded-lg p-3 border border-gray-100">
                <div className="flex-1">
                  <div className="flex items-center gap-2">
                    <h5 className="font-medium text-gray-800 text-sm">{f.name || f.title || f.feature}</h5>
                    {f.priority && (
                      <span className="px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded text-[10px] font-bold">
                        {f.priority}
                      </span>
                    )}
                  </div>
                  {f.description && (
                    <p className="text-xs text-gray-500 mt-0.5">{f.description}</p>
                  )}
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {successCriteria && (
        <div>
          <h4 className="text-sm font-semibold text-gray-800 uppercase tracking-wider mb-2">Success Criteria</h4>
          {typeof successCriteria === "string" ? (
            <p className="text-sm text-gray-600">{successCriteria}</p>
          ) : (
            <p className="text-sm text-gray-600">{JSON.stringify(successCriteria)}</p>
          )}
        </div>
      )}
    </div>
  );
}
